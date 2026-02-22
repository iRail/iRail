package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.OccupancyReport;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import com.fasterxml.jackson.databind.JsonNode;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.jspecify.annotations.NonNull;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;

public abstract class RivClient {
    private static final Logger log = LogManager.getLogger(RivClient.class);

    public List<DepartureAndArrival> parseVehicleStops(StationsDao stationsDao, OccupancyDao occupancyDao, JsonNode json, Vehicle vehicle, Language language) {
        List<DepartureAndArrival> stops = new ArrayList<>();
        JsonNode stopsNode = json.get("Stops").get("Stop");

        for (JsonNode rawStop : stopsNode) {
            DepartureAndArrival stop = parseHafasIntermediateStop(stationsDao, rawStop, vehicle, language);
            if (stop.hasDeparture()) {
                stop.getDeparture().setOccupancy(getOccupancy(occupancyDao, stop.getDeparture()));
            }
            stops.add(stop);
        }

        boolean foundLaterReportedArrival = false;
        for (int i = stops.size() - 1; i >= 0; i--) {
            DepartureAndArrival stop = stops.get(i);
            if (stop.hasDeparture() && stop.getDeparture().getStatus() == DepartureArrivalState.REPORTED && stop.hasArrival()) {
                stop.getArrival().setStatus(DepartureArrivalState.REPORTED);
            }

            if (foundLaterReportedArrival) {
                if (stop.hasArrival()) {
                    stop.getArrival().setStatus(DepartureArrivalState.REPORTED);
                }
                // departure is always present as this line cannot be reached for the last stop
                stop.getDeparture().setStatus(DepartureArrivalState.REPORTED);
            }

            if (!foundLaterReportedArrival && stop.hasArrival() && stop.getArrival().getStatus() == DepartureArrivalState.REPORTED) {
                foundLaterReportedArrival = true;
            }

        }

        return stops;
    }


    private DepartureAndArrival parseHafasIntermediateStop(StationsDao stationsDao, JsonNode rawStop, Vehicle vehicle, Language language) {
        String hafasId = rawStop.get("extId").asText();
        Station dbStation = stationsDao.getStationFromId("00" + hafasId);
        StationDto currentStation = convertToModelStation(dbStation, language);

        DepartureAndArrival departureAndArrival = new DepartureAndArrival();

        // Arrival
        if (rawStop.has("arrTime")) {
            departureAndArrival.setArrival(parseStopPart(rawStop, currentStation, vehicle, true));
        }

        // Departure
        if (rawStop.has("depTime")) {
            departureAndArrival.setDeparture(parseStopPart(rawStop, currentStation, vehicle, false));
        }

        return departureAndArrival;
    }

    public DepartureOrArrival parseStopPart(JsonNode rawStop, StationDto station, Vehicle vehicle, boolean isArrival) {
        String prefix = isArrival ? "Arr" : "Dep";
        String timeS = rawStop.get(prefix.toLowerCase() + "Time").asText();
        String dateS = rawStop.get(prefix.toLowerCase() + "Date").asText();
        LocalDateTime ldt = parseTimeAndDateCombination(timeS, dateS);

        int delay = 0;
        if (rawStop.has("rt" + prefix + "Time")) {
            String timeR = rawStop.get("rt" + prefix + "Time").asText();
            String dateR = rawStop.get("rt" + prefix + "Date").asText();
            LocalDateTime ldtR = parseTimeAndDateCombination(timeR, dateR);
            delay = (int) java.time.Duration.between(ldt, ldtR).getSeconds();
        }

        DepartureOrArrival part = new DepartureOrArrival();
        part.setStation(station);
        part.setVehicle(vehicle);
        part.setScheduledDateTime(ldt);
        part.setDelay(delay);

        String platform = getFieldOrNull(rawStop, prefix.toLowerCase() + "Track");
        String realtimePlatform = getFieldOrNull(rawStop, "rt" + prefix + "Track");
        boolean platformChanged = realtimePlatform != null;
        platform = platformChanged ? realtimePlatform : platform;

        if (!isArrival) {
            String depPrognosisType = getFieldOrNull(rawStop, "depPrognosisType");
            if (depPrognosisType != null) {
                part.setStatus("REPORTED".equals(depPrognosisType) ? DepartureArrivalState.REPORTED : null);
                part.setIsCancelled(isDepartureCanceledBasedOnState(depPrognosisType));
            }
        } else {
            String arrPrognosisType = getFieldOrNull(rawStop, "arrPrognosisType");
            if (arrPrognosisType != null) {
                part.setStatus("REPORTED".equals(arrPrognosisType) ? DepartureArrivalState.REPORTED : null);
                part.setIsCancelled(isArrivalCanceledBasedOnState(arrPrognosisType));
            }
        }

        part.setPlatform(new PlatformInfo(station.getId(), platform, platformChanged));

        // TODO: read alighting/boarding booleans, see international trains
        return part;
    }

    /**
     * Check whether the status of the arrival equals cancelled.
     *
     * @param status The status to check.
     * @return bool True if the arrival is cancelled, or if the status has an unrecognized value.
     */
    public boolean isArrivalCanceledBasedOnState(String status) {
        return !status.equals("SCHEDULED") &&
                !status.equals("REPORTED") &&
                !status.equals("PROGNOSED") &&
                !status.equals("CALCULATED") &&
                !status.equals("CORRECTED") &&
                !status.equals("PARTIAL_FAILURE_AT_DEP");
    }

    /**
     * Check whether or not the status of the departure equals cancelled.
     *
     * @param status The status to check.
     * @return bool True if the departure is cancelled, or if the status has an unrecognized value.
     */
    public boolean isDepartureCanceledBasedOnState(String status) {
        return !status.equals("SCHEDULED") &&
                !status.equals("REPORTED") &&
                !status.equals("PROGNOSED") &&
                !status.equals("CALCULATED") &&
                !status.equals("CORRECTED") &&
                !status.equals("PARTIAL_FAILURE_AT_ARR");
    }

    private static String getFieldOrNull(JsonNode rawStop, String field) {
        return rawStop.has(field) ? rawStop.get(field).asText() : null;
    }

    private static @NonNull LocalDateTime parseTimeAndDateCombination(String timeS, String dateS) {
        if (timeS.length() == 5 || timeS.length() == 7) {
            timeS = "0" + timeS;
        }
        // A leading 0 may be needed on time
        LocalDateTime ldt = LocalDateTime.parse(dateS + timeS, DateTimeFormatter.ofPattern("yyyy-MM-ddHH:mm:ss"));
        return ldt;
    }

    private OccupancyInfo getOccupancy(OccupancyDao occupancyDao, DepartureOrArrival stop) {
        List<OccupancyReport> reports = occupancyDao.getReportsForJourney(
                stop.getVehicle().getId(),
                stop.getScheduledDateTime().toLocalDate()
        );

        OccupancyLevel official = OccupancyLevel.UNKNOWN;
        OccupancyLevel spitsgids = OccupancyLevel.UNKNOWN;

        Integer stopId = extractNumericStopId(stop.getStation().getId());

        for (OccupancyReport report : reports) {
            if (report.getStopId().equals(stopId)) {
                if (report.getSource() == OccupancyReport.OccupancyReportSource.NMBS) {
                    official = mapOccupancyLevel(report.getOccupancy());
                } else if (report.getSource() == OccupancyReport.OccupancyReportSource.SPITSGIDS) {
                    spitsgids = mapOccupancyLevel(report.getOccupancy());
                }
            }
        }

        return new OccupancyInfo(official, spitsgids);
    }

    private Integer extractNumericStopId(String stationId) {
        if (stationId == null) {
            return null;
        }
        try {
            return Integer.parseInt(stationId.replaceAll("\\D+", ""));
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private OccupancyLevel mapOccupancyLevel(OccupancyReport.OccupancyLevel dbLevel) {
        if (dbLevel == null) {
            return OccupancyLevel.UNKNOWN;
        }
        return switch (dbLevel) {
            case LOW -> OccupancyLevel.LOW;
            case MEDIUM -> OccupancyLevel.MEDIUM;
            case HIGH -> OccupancyLevel.HIGH;
        };
    }


    protected StationDto convertToModelStation(Station dbStation, Language language) {
        if (dbStation == null) {
            return null;
        }
        return new StationDto(
                dbStation.getIrailId(),
                dbStation.getUri(),
                dbStation.getName(),
                dbStation.getName(language),
                dbStation.getLongitude(),
                dbStation.getLatitude()
        );
    }


    protected List<String> parseNotes(JsonNode tripNode) {
        List<String> notes = new ArrayList<>();
        if (tripNode.has("Notes") && tripNode.get("Notes").has("Note")) {
            for (JsonNode note : tripNode.get("Notes").get("Note")) {
                notes.add(note.get("value").asText());
            }
        }
        return notes;
    }


    protected LocalDate extractStartDate(String ref) {
        // Extract start date from ref
        String[] refParts = ref.split("\\|");
        LocalDate journeyStartDate = null;
        if (refParts.length >= 5) {
            // Parse the start date from "1|257447|0|80|1022026"
            if (refParts[4].length() == 7) {
                journeyStartDate = LocalDate.parse("0" + refParts[4], DateTimeFormatter.ofPattern("ddMMyyyy"));
            } else {
                journeyStartDate = LocalDate.parse(refParts[4], DateTimeFormatter.ofPattern("ddMMyyyy"));
            }
        } else {
            log.warn("Could not parse journey start date from ref: {}", ref);
        }
        return journeyStartDate;
    }
}
