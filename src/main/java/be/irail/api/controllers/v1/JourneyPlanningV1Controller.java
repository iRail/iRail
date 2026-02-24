package be.irail.api.controllers.v1;

import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.TimeSelection;
import be.irail.api.dto.result.JourneyPlanningSearchResult;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.notfound.IrailNotFoundException;
import be.irail.api.exception.request.BadRequestException;
import be.irail.api.exception.request.RequestedStopNotFoundException;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.JourneyPlanningV1Converter;
import be.irail.api.riv.NmbsRivJourneyPlanningClient;
import be.irail.api.riv.TypeOfTransportFilter;
import be.irail.api.riv.requests.JourneyPlanningRequest;
import be.irail.api.util.RequestParser;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.jspecify.annotations.NonNull;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;
import java.util.List;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

import static be.irail.api.config.Metrics.V1_JOURNEYPLANNING_REQUEST_METER;
import static be.irail.api.config.Metrics.V1_JOURNEYPLANNING_SUCCESS_REQUEST_METER;

/**
 * Controller for V1 Journey Planning (Connections) API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class JourneyPlanningV1Controller extends V1Controller {

    private static final Logger log = LogManager.getLogger(JourneyPlanningV1Controller.class);

    private final Cache<JourneyPlanningRequest, DataRoot> cache = CacheBuilder.newBuilder()
            .maximumSize(1000)
            .expireAfterWrite(2, TimeUnit.MINUTES)
            .build();
    private final NmbsRivJourneyPlanningClient journeyPlanningClient;
    private final StationsDao stationsDao;

    @Autowired
    public JourneyPlanningV1Controller(NmbsRivJourneyPlanningClient journeyPlanningClient, StationsDao stationsDao) {
        this.journeyPlanningClient = journeyPlanningClient;
        this.stationsDao = stationsDao;
    }

    /**
     * Gets connections between two stations.
     *
     * @param from            the origin station
     * @param to              the destination station
     * @param date            the date for the journey (ddmmyy)
     * @param time            the time for the journey (hhmm)
     * @param timesel         departure or arrival time selection
     * @param typeOfTransport the type of transport filter
     * @param lang            the language for response data
     * @param format          the response format
     * @return the journey planning result
     */
    @GET
    @Path("/connections")
    public Response getConnections(
            @QueryParam("from") String from,
            @QueryParam("to") String to,
            @QueryParam("date") String date,
            @QueryParam("time") String time,
            @QueryParam("timeSel") @DefaultValue("departure") String timesel,
            @QueryParam("typeOfTransport") @DefaultValue("automatic") String typeOfTransport,
            @QueryParam("lang") @DefaultValue("en") String lang,
            @QueryParam("format") @DefaultValue("xml") String format) {
        V1_JOURNEYPLANNING_REQUEST_METER.mark();
        TimeSelection timeSelection = RequestParser.parseV1TimeSelection(timesel);
        Language language = RequestParser.parseLanguage(lang);
        Format outputFormat = RequestParser.parseFormat(format);
        TypeOfTransportFilter transportFilter = parseTypeOfTransport(typeOfTransport);

        Station fromDbStation = resolveStation(from, "from", "Missing required parameter 'from' for origin");
        Station toDbStation = resolveStation(to, "to", "Missing required parameter 'to' for destination");

        // Parse date and time
        LocalDateTime dateTime = parseDateTime(date, time);

        // Create journey planning request
        JourneyPlanningRequest request = new JourneyPlanningRequest(
                fromDbStation,
                toDbStation,
                dateTime,
                timeSelection,
                transportFilter,
                language
        );


        try {
            DataRoot dataRoot = cache.get(request, () -> loadJourneyPlanningResult(request));
            // Serialize to output format
            Response response = v1Response(dataRoot, outputFormat);
            V1_JOURNEYPLANNING_SUCCESS_REQUEST_METER.mark();
            return response;
        } catch (UncheckedExecutionException | ExecutionException exception) {
            if (exception.getCause() instanceof IrailNotFoundException nfe) {
                // don't log these exceptions with a stack trace etc
                log.info("Journey from {} to {} not found: " + nfe.getMessage(), request.from().getIrailId(), request.to().getIrailId());
                throw nfe;
            }
            log.error("Error fetching connections from {} to {}: {}", from, to, exception.getMessage(), exception);
            if (exception.getCause() instanceof IrailHttpException irailException) {
                throw irailException; // Don't modify exceptions which have been caught/handled already
            }
            throw new InternalProcessingException("Error fetching connections: " + exception.getCause().getMessage(), exception.getCause());
        }
    }

    private @NonNull DataRoot loadJourneyPlanningResult(JourneyPlanningRequest request) throws ExecutionException {
        // Fetch journey planning data
        log.debug("Fetching connections from {} to {}", request.from().getIrailId(), request.to().getIrailId());
        JourneyPlanningSearchResult journeyPlanningResult = journeyPlanningClient.getJourneyPlanning(request);
        log.debug("Found {} connections from {} to {}",
                journeyPlanningResult.getJourneys().size(), request.from().getIrailId(), request.to().getIrailId());

        // Convert to V1 format
        DataRoot dataRoot = JourneyPlanningV1Converter.convert(journeyPlanningResult);
        return dataRoot;
    }

    private Station resolveStation(String value, String field, String message) {
        // Validate required parameters
        if (value == null || value.isEmpty()) {
            throw new BadRequestException(message, field, value);
        }

        // Resolve origin station
        Station foundStation = findStationByIdOrName(value);
        if (foundStation == null) {
            throw new RequestedStopNotFoundException(field, value);
        }
        return foundStation;
    }

    /**
     * Resolves a station from various input formats (name, ID, URI).
     */
    private Station findStationByIdOrName(String stationInput) {
        Station station = stationsDao.getStationFromId(stationInput);
        if (station != null) {
            return station;
        }

        List<Station> stations = stationsDao.getStations(stationInput);
        if (!stations.isEmpty()) {
            return stations.getFirst();
        }

        return null;
    }

    /**
     * Parses the type of transport filter parameter.
     */
    private TypeOfTransportFilter parseTypeOfTransport(String typeOfTransport) {
        if (typeOfTransport == null || typeOfTransport.isEmpty()) {
            return TypeOfTransportFilter.AUTOMATIC;
        }
        try {
            return TypeOfTransportFilter.valueOf(typeOfTransport.toUpperCase());
        } catch (IllegalArgumentException e) {
            return TypeOfTransportFilter.AUTOMATIC;
        }
    }
}
