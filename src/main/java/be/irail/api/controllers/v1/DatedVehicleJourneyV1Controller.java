package be.irail.api.controllers.v1;

import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.result.VehicleJourneySearchResult;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.notfound.IrailNotFoundException;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.DatedVehicleJourneyV1Converter;
import be.irail.api.riv.NmbsRivVehicleJourneyClient;
import be.irail.api.riv.requests.VehicleJourneyRequest;
import be.irail.api.util.RequestParser;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.util.concurrent.ExecutionException;

import static be.irail.api.config.Metrics.V1_VEHICLE_REQUEST_METER;
import static be.irail.api.config.Metrics.V1_VEHICLE_SUCCESS_REQUEST_METER;

/**
 * Controller for V1 Vehicle (DatedVehicleJourney) API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class DatedVehicleJourneyV1Controller extends V1Controller {

    private static final Logger log = LoggerFactory.getLogger(DatedVehicleJourneyV1Controller.class);

    private final NmbsRivVehicleJourneyClient vehicleJourneyClient;

    @Autowired
    public DatedVehicleJourneyV1Controller(NmbsRivVehicleJourneyClient vehicleJourneyClient) {
        this.vehicleJourneyClient = vehicleJourneyClient;
    }

    /**
     * Gets information about a specific vehicle/train journey.
     *
     * @param id     the vehicle ID (e.g., "IC538", "BE.NMBS.IC538")
     * @param date   the date for the vehicle journey (ddmmyy)
     * @param lang   the language for response data
     * @param format the response format
     * @return the vehicle journey result
     */
    @GET
    @Path("/vehicle")
    public Response getVehicleById(
            @QueryParam("id") String id,
            @QueryParam("date") String date,
            @QueryParam("lang") @DefaultValue("en") String lang,
            @QueryParam("format") @DefaultValue("xml") String format) {
        V1_VEHICLE_REQUEST_METER.mark();
        Language language = RequestParser.parseLanguage(lang);
        Format outputFormat = RequestParser.parseFormat(format);

        if (id == null || id.isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Missing required parameter: id")
                    .build();
        }

        // Parse date
        LocalDateTime dateTime = parseDateTime(date);

        // Create vehicle journey request
        VehicleJourneyRequest request = new VehicleJourneyRequest(id, dateTime, null, language);

        log.debug("Fetching vehicle journey for {}", id);

        try {
            // Fetch vehicle journey data
            VehicleJourneySearchResult vehicleJourneyResult = vehicleJourneyClient.getDatedVehicleJourney(request);
            log.debug("Found {} stops for vehicle {}", vehicleJourneyResult.getStops().size(), id);

            // Convert to V1 format
            DataRoot dataRoot = DatedVehicleJourneyV1Converter.convert(vehicleJourneyResult);

            // Serialize to output format
            Response response = v1Response(dataRoot, outputFormat);
            V1_VEHICLE_SUCCESS_REQUEST_METER.mark();
            return response;
        } catch (UncheckedExecutionException | ExecutionException exception) {
            if (exception.getCause() instanceof IrailNotFoundException nfe) {
                // don't log these exceptions with a stack trace etc
                log.info("Vehicle {} not found: " + nfe.getMessage(), request.vehicleId());
                throw nfe;
            }
            log.error("Error fetching vehicle journey for {}: {}", id, exception.getMessage(), exception);
            if (exception.getCause() instanceof IrailHttpException irailException) {
                throw irailException; // Don't modify exceptions which have been caught/handled already
            }
            throw new InternalProcessingException("Error fetching vehicle journey", exception);
        }
    }

    /**
     * Parses V1 date parameter into LocalDateTime.
     */
    private LocalDateTime parseDateTime(String date) {
        LocalDate localDate = LocalDate.now();

        if (date != null && !date.isEmpty()) {
            try {
                localDate = LocalDate.parse(date, DateTimeFormatter.ofPattern("ddMMyy"));
            } catch (Exception ignored) {
                // Use current date if parsing fails
            }
        }

        LocalTime now = LocalTime.now();
        LocalTime queryTime = now
                .withMinute(0) // Round to hours for better caching
                .withSecond(0)
                .withNano(0);
        return LocalDateTime.of(localDate, queryTime);
    }
}
