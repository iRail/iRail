package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.OccupancyReport;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.VehicleJourneySearchResult;
import be.irail.api.exception.upstream.UpstreamServerException;
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
            throw new UpstreamServerException("The server did not return any data.");
        }

        Vehicle vehicle = getVehicleDetails(json, request);
        List<DepartureAndArrival> stops = parseVehicleStops(stationsDao,occupancyDao, json, vehicle, request.language());
        List<Message> alerts = getAlerts(json);

        return new VehicleJourneySearchResult(vehicle, stops, alerts);
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

}
