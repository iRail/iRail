package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.JourneyPlanningSearchResult;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.riv.requests.JourneyPlanningRequest;
import com.fasterxml.jackson.databind.JsonNode;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.Objects;
import java.util.concurrent.ExecutionException;

/**
 * Client for fetching and parsing NMBS Journey Planning data.
 */
@Service
public class NmbsRivJourneyPlanningClient extends RivClient {
    private static final Logger log = LogManager.getLogger(NmbsRivJourneyPlanningClient.class);

    private final NmbsRivRawDataRepository rivDataRepository;
    private final StationsDao stationsDao;
    private final OccupancyDao occupancyDao;

    public NmbsRivJourneyPlanningClient(
            NmbsRivRawDataRepository rivDataRepository,
            StationsDao stationsDao,
            OccupancyDao occupancyDao
    ) {
        this.rivDataRepository = rivDataRepository;
        this.stationsDao = stationsDao;
        this.occupancyDao = occupancyDao;
    }

    public JourneyPlanningSearchResult getJourneyPlanning(JourneyPlanningRequest request) throws ExecutionException {
        CachedData<JsonNode> data = rivDataRepository.getRoutePlanningData(request);
        return parseJourneyPlanning(request, data);
    }

    private JourneyPlanningSearchResult parseJourneyPlanning(JourneyPlanningRequest request, CachedData<JsonNode> data) {
        JsonNode json = data.getValue();
        if (json == null || json.isNull()) {
            throw new UpstreamServerException("The server did not return any data.");
        }

        JourneyPlanningSearchResult result = new JourneyPlanningSearchResult();
        result.setOriginStation(convertToModelStation(request.from(), request.language()));
        result.setDestinationStation(convertToModelStation(request.to(), request.language()));

        List<Journey> journeys = new ArrayList<>();
        if (json.has("Trip") && json.get("Trip").isArray()) {
            for (JsonNode tripNode : json.get("Trip")) {
                journeys.add(parseHafasTrip(request, tripNode));
            }
        }
        result.setJourneys(journeys);
        return result;
    }

    private Journey parseHafasTrip(JourneyPlanningRequest request, JsonNode tripNode) {
        Journey journey = new Journey();
        List<JourneyLeg> legs = new ArrayList<>();

        if (tripNode.has("LegList") && tripNode.get("LegList").has("Leg")) {
            for (JsonNode legNode : tripNode.get("LegList").get("Leg")) {
                legs.add(parseHafasConnectionLeg(legNode, request));
            }
        }

        journey.setLegs(legs);
        journey.setNotes(parseNotes(tripNode));
        // Alerts could be parsed here if needed

        return journey;
    }

    private JourneyLeg parseHafasConnectionLeg(JsonNode legNode, JourneyPlanningRequest request) {
        DepartureOrArrival departure = parseConnectionLegEnd(legNode.get("Origin"), request.language());
        DepartureOrArrival arrival = parseConnectionLegEnd(legNode.get("Destination"), request.language());

        JourneyLeg parsedLeg = new JourneyLeg(departure, arrival);
        parsedLeg.setReachable(!legNode.has("reachable") || legNode.get("reachable").asBoolean());

        if ("WALK".equals(legNode.get("type").asText())) {
            parsedLeg.setLegType(JourneyLegType.WALKING);
            parsedLeg.setVehicle(null);
            return parsedLeg;
        }
        parsedLeg.setLegType(JourneyLegType.JOURNEY);

        JsonNode product = legNode.get("Product");
        String trainType = product.get("catOutL").asText().trim();
        if (!product.has("num")) {
            log.warn("Could not parse train number from leg: {}", legNode);
        }
        int trainNumber = product.get("num").asInt();
        String ref = legNode.get("JourneyDetailRef").get("ref").asText();
        LocalDate journeyStartDate = extractStartDate(ref);
        if (journeyStartDate != null) {
            rivDataRepository.cacheDiscoveredJourneyReference(trainNumber, journeyStartDate, ref);
        }
        journeyStartDate = Objects.requireNonNullElse(journeyStartDate, LocalDate.now());
        Vehicle vehicle = Vehicle.fromTypeAndNumber(trainType, trainNumber, journeyStartDate);

        String directionName = legNode.has("direction") ? legNode.get("direction").asText() : "Unknown";
        String[] directionParts = directionName.split("&");
        String lastStationName = directionParts[directionParts.length - 1].trim();

        // Try to find direction station by name (best effort)
        List<Station> found = stationsDao.getStations(lastStationName);
        StationDto directionStation = !found.isEmpty() ? convertToModelStation(found.getFirst(), request.language()) : null;
        if (found.isEmpty()) {
            log.warn("Direction station not found: " + lastStationName);
        }
        vehicle.setDirection(new VehicleDirection(directionName, directionStation));
        parsedLeg.setVehicle(vehicle);
        // Set occupancy for departure
        registerOccupancy(occupancyDao, departure, legNode.get("Origin"));
        departure.setOccupancy(occupancyDao.getOccupancy(departure));

        List<DepartureAndArrival> parsedStops = parseVehicleStops(stationsDao, occupancyDao, legNode, vehicle, request.language());
        List<DepartureAndArrival> intermediateStops = parsedStops.subList(1, parsedStops.size() - 1);
        parsedLeg.setIntermediateStops(intermediateStops);

        return parsedLeg;
    }


    private DepartureOrArrival parseConnectionLegEnd(JsonNode node, Language language) {
        DepartureOrArrival end = new DepartureOrArrival();
        Station dbStation = stationsDao.getStationFromId("00" + node.get("extId").asText());
        end.setStation(convertToModelStation(dbStation, language));

        String dateS = node.get("date").asText();
        String timeS = node.get("time").asText();
        LocalDateTime ldt = LocalDateTime.parse(dateS + timeS, DateTimeFormatter.ofPattern("yyyy-MM-ddHH:mm:ss"));
        end.setScheduledDateTime(ldt);

        if (node.has("rtTime")) {
            String rtDateS = node.get("rtDate").asText();
            String rtTimeS = node.get("rtTime").asText();
            LocalDateTime rtLdt = LocalDateTime.parse(rtDateS + rtTimeS, DateTimeFormatter.ofPattern("yyyy-MM-ddHH:mm:ss"));
            end.setDelay((int) java.time.Duration.between(ldt, rtLdt).getSeconds());
        }

        String platform = node.has("rtTrack") ? node.get("rtTrack").asText() : (node.has("track") ? node.get("track").asText() : null);
        boolean platformChanged = node.has("rtTrack") && node.has("track") && !node.get("rtTrack").asText().equals(node.get("track").asText());
        end.setPlatform(new PlatformInfo(end.getStation().getId(), platform, platformChanged));
        end.setStatus(node.has("prognosisType") && "REPORTED".equals(node.get("prognosisType").asText()) ? DepartureArrivalState.REPORTED : null);
        // TODO switch departure specific method to a method handling both departure and arrival
        end.setIsCancelled(node.has("prognosisType") && isDepartureCanceledBasedOnState(node.get("prognosisType").asText()));

        // A different way cancelled trains can be presented
        boolean cancelled = getBooleanOrDefault(node, "cancelled", false);
        boolean rtAlighting = getBooleanOrDefault(node, "rtAlighting", true);
        boolean rtBoarding = getBooleanOrDefault(node, "rtBoarding", true);
        if (rtAlighting || rtBoarding) {
            cancelled = false; // the cancelled flag is set even when only a departure or arrival is cancelled, fix this
        }
        if (node.has("rtAlighting") || cancelled) {
            // if alighting information is set, or completely cancelled
            end.setIsCancelled(cancelled || !rtAlighting);
        } else if (node.has("rtBoarding")) {
            // if boarding information is set, or completely cancelled
            end.setIsCancelled(cancelled || !rtBoarding);
        }
        return end;
    }

}
