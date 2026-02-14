package be.irail.api.riv;

import be.irail.api.config.Metrics;
import be.irail.api.dto.CachedData;
import be.irail.api.dto.TimeSelection;
import be.irail.api.exception.IrailConfigurationException;
import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.JourneyNotFoundException;
import be.irail.api.exception.JourneyPlanNotFoundException;
import be.irail.api.exception.upstream.UpstreamRateLimitException;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.exception.upstream.UpstreamServerParameterException;
import be.irail.api.exception.upstream.UpstreamServerUnavailableException;
import be.irail.api.gtfs.dao.GtfsTripStartEndExtractor;
import be.irail.api.riv.requests.JourneyPlanningRequest;
import be.irail.api.riv.requests.LiveboardRequest;
import be.irail.api.riv.requests.VehicleJourneyRequest;
import be.irail.api.util.VehicleIdTools;
import com.codahale.metrics.Meter;
import com.codahale.metrics.Timer;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.util.concurrent.UncheckedExecutionException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Repository;

import java.io.IOException;
import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

/**
 * Repository for fetching raw data from the NMBS RIV (Mobile) API.
 * Handles rate limiting, caching, and outgoing requests.
 */
@Repository
public class NmbsRivRawDataRepository {
    private static final Logger log = LoggerFactory.getLogger(NmbsRivRawDataRepository.class);

    private final GtfsTripStartEndExtractor gtfsTripStartEndExtractor;
    private final HttpClient httpClient;
    private final ObjectMapper objectMapper;
    private static final Cache<String, CachedData<JsonNode>> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(1, TimeUnit.MINUTES)
            .build();
    private static final Cache<JourneyDetailRefKey, Optional<String>> journeyDetailRefCache = CacheBuilder.newBuilder()
            .maximumSize(1000)
            .expireAfterWrite(4, TimeUnit.HOURS)
            .build();

    private final Timer rivHttpRequestTimer = Metrics.getRegistry().timer("RIV-outgoing-requests");
    private final Timer rivLiveboardTimer = Metrics.getRegistry().timer("RIV-liveboard");
    private final Timer rivRouteplanningTimer = Metrics.getRegistry().timer("RIV-routeplanning");
    private final Timer rivVehicleJourneyTimer = Metrics.getRegistry().timer("RIV-vehicleJourney");
    private final Timer rivVehicleJourneyDetailRefTimer = Metrics.getRegistry().timer("RIV-vehicleJourneyReference");
    private final Meter journeyRefDiscoveryMeter = Metrics.getRegistry().meter("RIV-JourneyDetailRef-discovery");

    private final int rateLimit;
    private final String apiKey;

    public NmbsRivRawDataRepository(
            GtfsTripStartEndExtractor gtfsTripStartEndExtractor,
            @Value("${nmbs.riv.limitRpm:10}") int rateLimit,
            @Value("${nmbs.riv.key:}") String apiKey
    ) {
        this.gtfsTripStartEndExtractor = gtfsTripStartEndExtractor;
        this.rateLimit = rateLimit;
        this.apiKey = apiKey;
        this.httpClient = HttpClient.newBuilder()
                .followRedirects(HttpClient.Redirect.NORMAL)
                .build();
        this.objectMapper = new ObjectMapper();
    }

    public CachedData<JsonNode> getLiveboardData(LiveboardRequest request) throws ExecutionException {
        try (var timer = rivLiveboardTimer.time()) {
            String hafasId = request.station().getHafasId();
            int roundedSeconds = request.dateTime().getSecond() - request.dateTime().getSecond() % 30;
            String formattedDateTime = request.dateTime().withSecond(roundedSeconds).format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"));

            Map<String, String> params = new HashMap<>();
            params.put("query", request.timeSelection() == TimeSelection.ARRIVAL ? "ArrivalsApp" : "DeparturesApp");
            params.put("UicCode", hafasId);
            params.put("FromDate", formattedDateTime);
            params.put("Count", "100");
            return makeApiCallToMobileRivApi("https://mobile-riv.api.belgianrail.be/api/v1.0/dacs", params);
        }
    }

    public CachedData<JsonNode> getRoutePlanningData(JourneyPlanningRequest request) throws ExecutionException {
        try (var timer = rivRouteplanningTimer.time()) {
            String fromId = request.from().getHafasId();
            String toId = request.to().getHafasId();
            String date = request.dateTime().format(DateTimeFormatter.ofPattern("yyyy-MM-dd"));
            String time = request.dateTime().withSecond(0).format(DateTimeFormatter.ofPattern("HH:mm:ss"));
            String searchForArrival = request.timeSelection() == be.irail.api.dto.TimeSelection.ARRIVAL ? "1" : "0";

            int transportBitcode = NmbsRivApiTransportTypeFilter.forTypeOfTransportFilter(fromId, toId, request.typesOfTransport()).getBitcode();

            Map<String, String> params = new HashMap<>();
            params.put("originExtId", fromId);
            params.put("destExtId", toId);
            params.put("date", date);
            params.put("time", time);
            params.put("lang", request.language().name());
            params.put("passlist", "1");
            params.put("searchForArrival", searchForArrival);
            params.put("numF", "6");
            params.put("products", String.valueOf(transportBitcode));

            return makeApiCallToMobileRivApi("https://mobile-riv.api.belgianrail.be/riv/v1.0/journey", params);
        }
    }

    public CachedData<JsonNode> getVehicleJourneyData(VehicleJourneyRequest request) throws ExecutionException {
        try (var timer = rivVehicleJourneyTimer.time()) {
            String language = request.language() != null ? request.language().name() : "en";
            int journeyNumber = VehicleIdTools.extractTrainNumber(request.vehicleId());
            var cacheKey = new JourneyDetailRefKey(journeyNumber, request.dateTime().toLocalDate());

            Optional<String> journeyDetailRef = journeyDetailRefCache.get(cacheKey,
                    () -> Optional.ofNullable(getJourneyDetailRef(journeyNumber, request))
            );
            String journeyDetailRefValue = journeyDetailRef
                    .orElseThrow(() -> new JourneyNotFoundException(request.vehicleId(), request.dateTime().toLocalDate(), "JourneyDetailRef not found"));
            try {
                return getJourneyDetailResponse(journeyDetailRefValue, language);
            } catch (UpstreamServerParameterException e) {
                // A cached journeyDetailRef may no longer be valid
                log.warn("JourneyDetailRef {} is no longer valid, fetching new one", journeyDetailRefValue);
                journeyDetailRefCache.invalidate(cacheKey);
                journeyDetailRef = journeyDetailRefCache.get(cacheKey,
                        () -> Optional.ofNullable(getJourneyDetailRef(journeyNumber, request))
                );
                journeyDetailRefValue = journeyDetailRef
                        .orElseThrow(() -> new JourneyNotFoundException(request.vehicleId(), request.dateTime().toLocalDate(), "JourneyDetailRef not found"));
                log.warn("Obtained new journey detail ref {}", journeyDetailRefValue);
                return getJourneyDetailResponse(journeyDetailRefValue, language);
            }
        }
    }

    /**
     * Get the journey detail reference by searching for the vehicle in the RIV API.
     */
    private String getJourneyDetailRef(int journeyNumber, VehicleJourneyRequest request) {
        try (var timer = rivVehicleJourneyDetailRefTimer.time()) {
            log.debug("Fetching journey detail reference for vehicle {} on {}", journeyNumber, request.dateTime());
            // We need to find the vehicle so we can add the correct journey type, since a request can come in with or without it
            // We want to treat all requests identically to ensure correct responses in all cases
            Optional<JourneyWithOriginAndDestination> optVehicle = gtfsTripStartEndExtractor.getVehicleWithOriginAndDestination(
                    journeyNumber, request.dateTime());

            if (optVehicle.isEmpty()) {
                log.debug("Could not find vehicle {} in GTFS data for date {}", journeyNumber, request.dateTime());
                return null;
            }
            if (optVehicle.get().getJourneyType().equals("BUS")) {
                // Dont even attempt to make a request
                log.debug("Cannot get journey detail ref for bus traffic, this doesn't work in the NMBS app either");
                return null;
            }
            JourneyWithOriginAndDestination vehicle = optVehicle.get();

            String journeyDetailRef = findVehicleJourneyRefBetweenStops(request, vehicle);

            // If not found, the journey might have been partially cancelled. Try alternative segments.
            if (journeyDetailRef == null) {
                journeyDetailRef = getJourneyDetailRefAlt(request, vehicle);
            }

            if (journeyDetailRef != null) {
                log.debug("Found journey detail ref: '{}' between {} and {}",
                        journeyDetailRef, vehicle.getOriginStopId(), vehicle.getDestinationStopId());
                return journeyDetailRef;
            } else {
                log.warn("Failed to find journey ref for {}", request.vehicleId());
                return null;
            }
        }
    }

    /**
     * Find the vehicle journey reference by searching between origin and destination stops.
     */
    private String findVehicleJourneyRefBetweenStops(VehicleJourneyRequest request, JourneyWithOriginAndDestination
            vehicle) {
        String formattedDate = request.dateTime().format(DateTimeFormatter.ofPattern("yyyy-MM-dd"));
        String vehicleName = vehicle.getJourneyType() + vehicle.getJourneyNumber();

        Map<String, String> params = new HashMap<>();
        params.put("trainFilter", vehicleName);
        params.put("originExtId", vehicle.getOriginStopId());
        params.put("destExtId", vehicle.getDestinationStopId());
        params.put("date", formattedDate);

        try {
            CachedData<JsonNode> response = makeApiCallToMobileRivApi("https://mobile-riv.api.belgianrail.be/riv/v1.0/journey", params);
            if (response.getValue() == null || !response.getValue().has("Trip")) {
                return null;
            }
            return response.getValue().path("Trip").path(0).path("LegList").path("Leg").path(0).path("JourneyDetailRef").path("ref").asText(null);
        } catch (Exception e) {
            log.debug("Failed to find journey ref between stops: {}", e.getMessage());
            return null;
        }
    }

    /**
     * Get the journey detail reference by trying alternative origin-destination stretches,
     * to cope with cancelled origin/destination stops.
     */
    private String getJourneyDetailRefAlt(VehicleJourneyRequest request, JourneyWithOriginAndDestination vehicle) {
        List<JourneyWithOriginAndDestination> alternatives = gtfsTripStartEndExtractor.getAlternativeVehicleWithOriginAndDestination(vehicle);

        int i = 0;
        String journeyRef = null;
        while (journeyRef == null && i < alternatives.size() / 2) {
            JourneyWithOriginAndDestination alt = alternatives.get(i);
            log.debug("Searching for vehicle {} using alternative segments: {} - {}, {}", request.vehicleId(), alt.getOriginStopId(), alt.getDestinationStopId(), i);
            journeyRef = findVehicleJourneyRefBetweenStops(request, alt);

            if (journeyRef == null) {
                // Alternate searching from the front and the back, since cancelled first/last stops are the most common.
                int j = (alternatives.size() - 1) - i;
                log.debug("Searching for vehicle {} using alternative segments {} - {}, {}", request.vehicleId(), alt.getOriginStopId(), alt.getDestinationStopId(), j);
                alt = alternatives.get(j);
                journeyRef = findVehicleJourneyRefBetweenStops(request, alt);
            }
            i++;
        }
        return journeyRef;
    }

    private CachedData<JsonNode> getJourneyDetailResponse(String journeyDetailRef, String language) throws
            ExecutionException {
        Map<String, String> params = new HashMap<>();
        params.put("id", journeyDetailRef);
        params.put("lang", language);
        return makeApiCallToMobileRivApi("https://mobile-riv.api.belgianrail.be/riv/v1.0/journey/detail", params);
    }

    public CachedData<JsonNode> getVehicleCompositionData(String trainNumber, String fromUic, String
            toUic, LocalDate date) throws ExecutionException {
        Map<String, String> params = new HashMap<>();
        params.put("TrainNumber", trainNumber);
        params.put("From", fromUic);
        params.put("To", toUic);
        params.put("date", date.format(DateTimeFormatter.ofPattern("yyyy-MM-dd")));
        params.put("FromToAreUicCodes", "true");
        params.put("IncludeMaterialInfo", "true");
        return makeApiCallToMobileRivApi(
                "https://mobile-riv.api.belgianrail.be/api/v1/commercialtraincompositionsbetweenptcars", params);
    }

    private CachedData<JsonNode> makeApiCallToMobileRivApi(String endpoint, Map<String, String> parameters) throws
            ExecutionException {
        StringBuilder urlBuilder = new StringBuilder(endpoint).append("?");
        parameters.forEach((k, v) -> urlBuilder
                .append(URLEncoder.encode(k, StandardCharsets.UTF_8))
                .append("=")
                .append(URLEncoder.encode(v, StandardCharsets.UTF_8))
                .append("&"));
        if (!parameters.isEmpty()) {
            // Remove trailing &, NMBS may return HTTP 500 if its present
            urlBuilder.deleteCharAt(urlBuilder.length() - 1);
        }
        String url = urlBuilder.toString();
        try {
            return cache.get(url, () -> {
                String response = fetchRateLimitedRivResponse(url);

                // Check for timeout response before parsing as JSON
                if (response.startsWith("{\"exception\":\"Hacon response time exceeded the defined timeout")) {
                    throw new UpstreamServerException("The upstream server encountered a timeout while loading data. Please try again later.");
                }

                try {
                    JsonNode node = objectMapper.readTree(response);
                    throwExceptionOnInvalidResponse(node);
                    return new CachedData<>(node, 60);
                } catch (IOException e) {
                    log.error("Failed to parse RIV response: {}", response, e);
                    throw new UpstreamServerException("iRail could not read the data received from the remote server.", e);
                }
            });
        } catch (ExecutionException | UncheckedExecutionException e) {
            if (e.getCause() instanceof IrailHttpException irailHttpException) {
                throw irailHttpException;
            }
            throw e;
        }
    }

    private String fetchRateLimitedRivResponse(String url) {
        if (rivHttpRequestTimer.getOneMinuteRate() > rateLimit) {
            log.warn("Upstream rate limit exceeded. Current rate {}", rivHttpRequestTimer.getCount());
            throw new UpstreamRateLimitException();
        }

        try (var timer = rivHttpRequestTimer.time()) {
            HttpRequest.Builder builder = HttpRequest.newBuilder()
                    .uri(URI.create(url))
                    .header("Accept", "application/json")
                    .header("x-api-key", apiKey);

            if (apiKey == null || apiKey.isEmpty()) {
                throw new IrailConfigurationException("API key is required for NMBS RIV requests");
            }

            HttpRequest httpRequest = builder.build();

            long start = System.currentTimeMillis();
            HttpResponse<String> response = httpClient.send(httpRequest, HttpResponse.BodyHandlers.ofString());
            long duration = System.currentTimeMillis() - start;
            log.debug("GET {}: {} in {}ms", url, response.statusCode(), duration);
            String body = response.body();
            log.trace(body);
            if (response.statusCode() >= 500) {
                throw new UpstreamServerException("Upstream server error: " + response.statusCode() + "\nBody: '" + body + "'");
            }
            return body;
        } catch (IOException | InterruptedException e) {
            Thread.currentThread().interrupt();
            throw new UpstreamServerException("Failed to fetch data from NMBS", e);
        }
    }

    private void throwExceptionOnInvalidResponse(JsonNode json) {
        if (json.has("errorCode")) {
            String errorCode = json.get("errorCode").asText();
            // Ported from BasedOnHafas.php
            switch (errorCode) {
                case "INT_ERR":
                case "INT_GATEWAY":
                    throw new UpstreamServerUnavailableException();
                case "INT_TIMEOUT":
                    throw new UpstreamServerException("The upstream server encountered a timeout while loading the data.");
                case "SVC_NO_RESULT":
                    throw new JourneyPlanNotFoundException();
                case "SVC_LOC":
                    throw new UpstreamServerException("Location not found");
                case "SVC_LOC_EQUAL":
                    throw new UpstreamServerException("Origin and destination location are the same");
                case "SVC_PARAM":
                    throw new UpstreamServerParameterException(json.get("errorText").asText());
                case "SVC_DATETIME_PERIOD":
                case "SVC_DATATIME_PERIOD":
                    throw new UpstreamServerException("Date outside of the timetable period. Check your query.");
                default:
                    throw new UpstreamServerException("This request failed. Please check your query. Error code " + errorCode);
            }
        }
    }

    /**
     * Store a journey reference ("1|257447|0|80|1022026") for a vehicle if it was discovered in another endpoint, this way we can avoid some complicated lookups!.
     *
     * @param journeyNumber    The journey number without type (e.g. 518)
     * @param journeyStartDate The start date
     * @param journeyDetailRef The discovered reference
     */
    public void cacheDiscoveredJourneyReference(int journeyNumber, LocalDate journeyStartDate, String journeyDetailRef) {
        JourneyDetailRefKey key = new JourneyDetailRefKey(journeyNumber, journeyStartDate);
        //noinspection OptionalAssignedToNull , the optional<String> is the generic, but can still be null in this particular case
        if (journeyDetailRefCache.getIfPresent(key) == null) {
            journeyRefDiscoveryMeter.mark(); // Keep track of how useful this is
            log.debug("Caching journey reference {} for vehicle {} on {} based on other responses", journeyDetailRef, journeyNumber, journeyStartDate);
            journeyDetailRefCache.put(key, Optional.ofNullable(journeyDetailRef));
        }
    }

    private record JourneyDetailRefKey(int journeyNumber, LocalDate journeyStartDate) {
    }
}
