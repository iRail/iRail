package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.OccupancyReport;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.LiveboardSearchResult;
import be.irail.api.exception.JourneyNotFoundException;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsTripStartEndExtractor;
import be.irail.api.riv.requests.LiveboardRequest;
import com.fasterxml.jackson.databind.JsonNode;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.OffsetDateTime;
import java.time.ZoneOffset;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutionException;

/**
 * Client for fetching and parsing NMBS Liveboard data.
 */
@Service
public class NmbsRivLiveboardClient {
    private static final Logger log = LogManager.getLogger(NmbsRivLiveboardClient.class);

    private final NmbsRivRawDataRepository rivDataRepository;
    private final StationsDao stationsDao;
    private final GtfsTripStartEndExtractor gtfsTripStartEndExtractor;
    private final OccupancyDao occupancyDao;

    private static final DateTimeFormatter DATE_TIME_FORMATTER = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

    public NmbsRivLiveboardClient(
            NmbsRivRawDataRepository rivDataRepository,
            StationsDao stationsDao,
            GtfsTripStartEndExtractor gtfsTripStartEndExtractor,
            OccupancyDao occupancyDao
    ) {
        this.rivDataRepository = rivDataRepository;
        this.stationsDao = stationsDao;
        this.gtfsTripStartEndExtractor = gtfsTripStartEndExtractor;
        this.occupancyDao = occupancyDao;
    }

    public LiveboardSearchResult getLiveboard(LiveboardRequest request) throws ExecutionException {
        CachedData<JsonNode> cachedRawData = rivDataRepository.getLiveboardData(request);
        return parseNmbsRawData(request, cachedRawData);
    }

    private LiveboardSearchResult parseNmbsRawData(LiveboardRequest request, CachedData<JsonNode> cachedRawData) {
        JsonNode rawData = cachedRawData.getValue();

        if (rawData == null || rawData.isNull()) {
            throw new UpstreamServerException("The server did not return any data.");
        }

        Station dbStation = request.station();
        StationDto currentStation = convertToModelStation(dbStation, request.language());

        List<DepartureOrArrival> departuresOrArrivals = new ArrayList<>();
        JsonNode entries = rawData.get("entries");
        if (entries != null && entries.isArray()) {
            for (JsonNode entry : entries) {
                if (isServiceTrain(entry)) {
                    continue;
                }
                DepartureOrArrival parsedStop = parseStopAtStation(request, currentStation, entry);
                if (parsedStop != null) {
                    departuresOrArrivals.add(parsedStop);
                }
            }
        }

        return new LiveboardSearchResult(currentStation, departuresOrArrivals);
    }

    private DepartureOrArrival parseStopAtStation(LiveboardRequest request, StationDto currentStation, JsonNode entry) {
        boolean isArrivalBoard = request.timeSelection() == TimeSelection.ARRIVAL;

        String plannedTimeStr = isArrivalBoard ? entry.get("PlannedArrival").asText() : entry.get("PlannedDeparture").asText();
        LocalDateTime plannedDateTime = LocalDateTime.parse(plannedTimeStr, DATE_TIME_FORMATTER);
        int delay = parseDelayInSeconds(entry, isArrivalBoard ? "ArrivalDelay" : "DepartureDelay");

        String platform = entry.has("Platform") ? entry.get("Platform").asText() : "?";
        boolean hasPlatformChanged = entry.has("PlatformChanged") && entry.get("PlatformChanged").asInt() == 1;

        boolean stopCanceled = entry.has("Status") && "Canceled".equals(entry.get("Status").asText());

        DepartureArrivalState status = null;
        if (entry.has("DepartureStatusNl")) {
            String statusNl = entry.get("DepartureStatusNl").asText();
            if ("aan perron".equals(statusNl)) {
                status = DepartureArrivalState.HALTING;
            }
        }

        int journeyNumber = entry.get("TrainNumber").asInt();
        String commercialType = entry.get("CommercialType").asText();

        // Use GTFS for journey start date if possible
        LocalDate journeyStartDate;
        try {
            if (!commercialType.equals("EUR") && !commercialType.equals("ICE")) {
                journeyStartDate = gtfsTripStartEndExtractor.getStartDate(journeyNumber, plannedDateTime);
            } else {
                // Don't even try to lookup Eurostar and ICE trains in the GTFS data
                journeyStartDate = plannedDateTime.toLocalDate();
            }
        } catch (JourneyNotFoundException e) {
            // Riv API contains trains which aren't present in the GTFS data
            journeyStartDate = plannedDateTime.toLocalDate();
        }
        Vehicle vehicle = Vehicle.fromTypeAndNumber(commercialType, journeyNumber, journeyStartDate);

        String directionUic = getDirectionUicCode(entry, isArrivalBoard, plannedDateTime);
        Station directionDbStation = stationsDao.getStationFromId(directionUic);
        if (directionDbStation == null) {
            log.error("Unknown station " + directionUic + " " + entry.get("DestinationNl"));
            return null;
        }

        StationDto directionStation = convertToModelStation(directionDbStation, request.language());
        String headSign;
        if (entry.has(isArrivalBoard ? "OriginNl" : "DestinationNl")) {
            headSign = entry.get(isArrivalBoard ? "OriginNl" : "DestinationNl").asText();
        } else {
            headSign = directionStation.getStationName();
        }
        vehicle.setDirection(new VehicleDirection(headSign, directionStation));

        DepartureOrArrival stopAtStation = new DepartureOrArrival();
        stopAtStation.setStation(currentStation);
        stopAtStation.setVehicle(vehicle);
        stopAtStation.setScheduledDateTime(plannedDateTime);
        stopAtStation.setDelay(delay);
        stopAtStation.setPlatform(new PlatformInfo(currentStation.getId(), platform, hasPlatformChanged));
        stopAtStation.setIsCancelled(stopCanceled);
        stopAtStation.setStatus(status);
        stopAtStation.setIsExtra(entry.has("status") && "A".equals(entry.get("status").asText()));

        // Occupancy
        stopAtStation.setOccupancy(getOccupancy(stopAtStation));

        return stopAtStation;
    }

    private int parseDelayInSeconds(JsonNode entry, String key) {
        if (!entry.has(key)) {
            return 0;
        }
        String delayStr = entry.get(key).asText();
        String[] parts = delayStr.split(":");
        if (parts.length == 3) {
            try {
                int hours = Integer.parseInt(parts[0]);
                int minutes = Integer.parseInt(parts[1]);
                int seconds = Integer.parseInt(parts[2]);
                return hours * 3600 + minutes * 60 + seconds;
            } catch (NumberFormatException e) {
                return 0;
            }
        }
        return 0;
    }

    private boolean isServiceTrain(JsonNode entry) {
        return entry.has("CommercialType") && "SERV".equals(entry.get("CommercialType").asText());
    }

    private String getDirectionUicCode(JsonNode entry, boolean isArrivalBoard, LocalDateTime plannedDateTime) {
        String key = isArrivalBoard ? "Origin1UicCode" : "Destination1UicCode";
        if (entry.has(key)) {
            return entry.get(key).asText();
        }

        JourneyWithOriginAndDestination gtfsJourney = gtfsTripStartEndExtractor.getVehicleWithOriginAndDestination(
                entry.get("TrainNumber").asInt(),
                plannedDateTime
        );

        if (gtfsJourney != null) {
            return isArrivalBoard ? gtfsJourney.getOriginStopId() : gtfsJourney.getDestinationStopId();
        }

        return "0000000"; // Fallback
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

    private StationDto convertToModelStation(Station dbStation, Language language) {
        if (dbStation == null) {
            return null;
        }
        return new StationDto(
                dbStation.getIrailId(),
                dbStation.getUri(),
                dbStation.getName(),
                dbStation.getName(language), // Default localized name to standard name
                dbStation.getLongitude(),
                dbStation.getLatitude()
        );
    }
}
