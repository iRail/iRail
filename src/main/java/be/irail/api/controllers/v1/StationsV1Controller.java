package be.irail.api.controllers.v1;

import be.irail.api.config.Metrics;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.exception.IrailHttpException;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.StationsV1Converter;
import be.irail.api.util.RequestParser;
import com.codahale.metrics.Meter;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.util.List;

/**
 * Controller for V1 Stations API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class StationsV1Controller extends V1Controller {

    private static final Logger logger = LoggerFactory.getLogger(StationsV1Controller.class);
    private final Meter requestMeter = Metrics.getRegistry().meter("Requests, Stations");
    private final StationsDao stationsDao;

    @Autowired
    public StationsV1Controller(StationsDao stationsDao) {
        this.stationsDao = stationsDao;
    }

    /**
     * Lists all available stations.
     *
     * @param format the response format (xml or json)
     * @param lang   the language for station names
     * @return a list of stations
     */
    @GET
    @Path("/stations")
    public Response listStations(
            @QueryParam("format") @DefaultValue("xml") String format,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        requestMeter.mark();
        Format outputFormat = RequestParser.parseFormat(format);
        Language language = RequestParser.parseLanguage(lang);

        try {
            // Get all stations from database
            List<Station> dbStations = stationsDao.getAllStations();

            // Convert to model stations with localized names
            List<StationDto> stations = dbStations.stream()
                    .map(dbStation -> dbStation.toDto(language))
                    .toList();

            // Convert to V1 format
            DataRoot dataRoot = StationsV1Converter.convert(stations, language);
            return v1Response(dataRoot, outputFormat);
        } catch (UncheckedExecutionException exception) {
            logger.error("Error fetching stations: {}", exception.getMessage(), exception);
            if (exception.getCause() instanceof IrailHttpException irailException) {
                throw irailException; // Don't modify exceptions which have been caught/handled already
            }
            throw new InternalProcessingException("Error fetching stations: " + exception.getMessage(), exception);
        }
    }

}
