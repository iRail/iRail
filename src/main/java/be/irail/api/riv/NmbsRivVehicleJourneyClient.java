package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.OccupancyReport;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.VehicleJourneySearchResult;
import be.irail.api.riv.requests.VehicleJourneyRequest;
import com.fasterxml.jackson.databind.JsonNode;
import org.jspecify.annotations.NonNull;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Objects;
import java.util.concurrent.ExecutionException;

/**
 * Client for fetching and parsing NMBS Vehicle Journey data.
 */
@Service
public class NmbsRivVehicleJourneyClient extends RivClient {

    private final NmbsRivRawDataRepository rivDataRepository;
    private final StationsDao stationsDao;
    private final OccupancyDao occupancyDao;

    public NmbsRivVehicleJourneyClient(
            NmbsRivRawDataRepository rivDataRepository,
            StationsDao stationsDao,
            OccupancyDao occupancyDao
    ) {
        this.rivDataRepository = rivDataRepository;
        this.stationsDao = stationsDao;
        this.occupancyDao = occupancyDao;
    }

    public VehicleJourneySearchResult getDatedVehicleJourney(VehicleJourneyRequest request) throws ExecutionException {
        CachedData<JsonNode> cachedRawData = rivDataRepository.getVehicleJourneyData(request);
        return parseNmbsRawVehicleJourney(request, cachedRawData);
    }

    private VehicleJourneySearchResult parseNmbsRawVehicleJourney(VehicleJourneyRequest request, CachedData<JsonNode> cachedRawData) {
        JsonNode json = cachedRawData.getValue();
        if (json == null || json.isNull()) {
            throw new RuntimeException("The server did not return any data.");
        }

        Vehicle vehicle = getVehicleDetails(json, request);
        List<DepartureAndArrival> stops = parseVehicleStops(json, vehicle, request.language());
        List<Message> alerts = getAlerts(json);

        return new VehicleJourneySearchResult(vehicle, stops, alerts);
    }

    private List<DepartureAndArrival> parseVehicleStops(JsonNode json, Vehicle vehicle, Language language) {
        List<DepartureAndArrival> stops = new ArrayList<>();
        JsonNode stopsNode = json.get("Stops").get("Stop");

        for (JsonNode rawStop : stopsNode) {
            DepartureAndArrival stop = parseHafasIntermediateStop(rawStop, vehicle, language);
            if (stop.getDeparture() != null) {
                stop.getDeparture().setOccupancy(getOccupancy(stop.getDeparture()));
            }
            stops.add(stop);
        }
        return stops;
    }

    private DepartureAndArrival parseHafasIntermediateStop(JsonNode rawStop, Vehicle vehicle, Language language) {
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

    private DepartureOrArrival parseStopPart(JsonNode rawStop, StationDto station, Vehicle vehicle, boolean isArrival) {
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
        boolean platformChanged = realtimePlatform != null && !realtimePlatform.equals(platform);
        platform = platformChanged ? realtimePlatform : platform;

        part.setPlatform(new PlatformInfo(station.getId(), platform, platformChanged));
        part.setIsCancelled(rawStop.has(prefix + "Cncl") && rawStop.get(prefix + "Cncl").asBoolean());

        return part;
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

    private Vehicle getVehicleDetails(JsonNode json, VehicleJourneyRequest request) {
        JsonNode nameNode = json.get("Names").get("Name").get(0);
        String trainType = nameNode.get("Product").get("catOutL").asText().trim();
        int trainNumber = nameNode.get("Product").get("num").asInt();

        // Extract start date from ref or use request date
        String ref = json.get("ref").asText();
        LocalDate journeyStartDate = extractStartDate(ref);
        journeyStartDate = Objects.requireNonNullElse(journeyStartDate, LocalDate.now());

        Vehicle vehicle = Vehicle.fromTypeAndNumber(trainType, trainNumber, journeyStartDate);
        setDirection(vehicle, json, request.language());
        return vehicle;
    }

    private void setDirection(Vehicle vehicle, JsonNode json, Language language) {
        JsonNode directions = json.get("Directions").get("Direction");
        JsonNode stops = json.get("Stops").get("Stop");
        JsonNode lastStop = stops.get(stops.size() - 1);

        String headSign = directions.get(0).has("value") ? directions.get(0).get("value").asText() : lastStop.get("name").asText();
        String destHafasId = lastStop.get("extId").asText();
        Station destDbStation = stationsDao.getStationFromId("00" + destHafasId);

        vehicle.setDirection(new VehicleDirection(headSign, convertToModelStation(destDbStation, language)));
    }

    private List<Message> getAlerts(JsonNode json) {
        if (!json.has("Messages")) {
            return Collections.emptyList();
        }
        // TODO Implement parsing if Message model is fully understood, for now return empty
        return new ArrayList<>();
    }

    private OccupancyInfo getOccupancy(DepartureOrArrival stop) {
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
}
