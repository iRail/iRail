package be.irail.api.controllers.v1;

import be.irail.api.db.CompositionDao;
import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.Vehicle;
import be.irail.api.dto.result.VehicleCompositionSearchResult;
import be.irail.api.dto.vehiclecomposition.TrainComposition;
import be.irail.api.exception.JourneyNotFoundException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.VehicleCompositionV1Converter;
import be.irail.api.riv.NmbsRivCompositionClient;
import be.irail.api.util.RequestParser;
import be.irail.api.util.VehicleIdTools;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.jspecify.annotations.NonNull;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.time.LocalDate;
import java.util.List;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

/**
 * Controller for V1 Composition API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class CompositionV1Controller extends V1Controller {
    private static final Logger log = LogManager.getLogger(CompositionV1Controller.class);

    private final CompositionDao compositionDao;
    private final NmbsRivCompositionClient compositionClient;

    private final Cache<CompositionRequest, DataRoot> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(15, TimeUnit.MINUTES)
            .build();

    @Autowired
    public CompositionV1Controller(
            CompositionDao compositionDao,
            NmbsRivCompositionClient compositionClient
    ) {
        this.compositionDao = compositionDao;
        this.compositionClient = compositionClient;
    }

    /**
     * Gets the composition of a specific vehicle/train.
     *
     * @param journeyId the vehicle ID
     * @param dateStr   the date (ddmmyy format)
     * @param lang      the language for response data
     * @param format    the response format
     * @return the vehicle composition result
     */
    @GET
    @Path("/composition")
    public Response getComposition(
            @QueryParam("id") String journeyId,
            @QueryParam("date") String dateStr,
            @QueryParam("lang") @DefaultValue("en") String lang,
            @QueryParam("format") @DefaultValue("xml") String format) {

        if (journeyId == null || journeyId.isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Missing required parameter: id")
                    .build();
        }

        Language language = RequestParser.parseLanguage(lang);
        Format outputFormat = RequestParser.parseFormat(format);

        try {
            LocalDate date = parseDate(dateStr);
            log.debug("Fetching composition for vehicle ID: " + journeyId + ", date: " + date);

            DataRoot dataRoot = getCachedData(journeyId, date, language);

            // Serialize to output format
            return v1Response(dataRoot, outputFormat);
        } catch (Exception exception) {
            log.error("Error fetching composition for vehicle {}: {}", journeyId, exception.getMessage(), exception);
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
                    .entity("Error fetching composition: " + exception.getMessage())
                    .build();
        }
    }

    private @NonNull DataRoot getCachedData(String journeyId, LocalDate date, Language language) throws ExecutionException {
        CompositionRequest request = new CompositionRequest(journeyId, date, language);
        return cache.get(request, () -> getDataRoot(request));
    }

    private DataRoot getDataRoot(CompositionRequest request) throws ExecutionException {
        log.debug("Fetching composition for vehicle ID: " + request.journeyId() + ", date: " + request.date() + " from database or RIV");
        int journeyNumber = VehicleIdTools.extractTrainNumber(request.journeyId());

        // Get journey type from GTFS
        Vehicle vehicle = GtfsInMemoryDao.getInstance().getVehicle(journeyNumber, request.date);

        // Get composition from database
        List<TrainComposition> compositionSegments = compositionDao.getComposition(vehicle, request.date);

        if (compositionSegments.isEmpty()) {
            log.debug("Composition for vehicle {} not found in database, fetching fresh data from RIV", request.journeyId());
            VehicleCompositionSearchResult freshData = compositionClient.getComposition(vehicle);
            if (freshData == null || freshData.getSegments().isEmpty()) {
                throw new JourneyNotFoundException(request.journeyId(), request.date(), "No composition data found for vehicle.");
            }
            log.debug("Composition for vehicle {} fetched from RIV, {} segments", request.journeyId, freshData.getSegments().size());
            compositionDao.storeComposition(vehicle, freshData);
            compositionSegments = freshData.getSegments();
        } else {
            log.debug("Composition for vehicle {} found in database, {} segments", request.journeyId, compositionSegments.size());
        }

        // Convert to V1 format
        return new VehicleCompositionV1Converter(request.language).convert(compositionSegments);
    }

    private record CompositionRequest(String journeyId, LocalDate date, Language language) {
    }
}
