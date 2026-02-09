package be.irail.api.controllers.v1;

import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.TimeSelection;
import be.irail.api.dto.result.LiveboardSearchResult;
import be.irail.api.exception.request.BadRequestException;
import be.irail.api.exception.request.RequestedStopNotFoundException;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.LiveboardV1Converter;
import be.irail.api.riv.NmbsRivLiveboardClient;
import be.irail.api.riv.requests.LiveboardRequest;
import be.irail.api.util.RequestParser;
import com.google.common.cache.CacheBuilder;
import com.google.common.cache.CacheLoader;
import com.google.common.cache.LoadingCache;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Objects;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

/**
 * Controller for V1 Liveboard API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class LiveboardV1Controller extends V1Controller {

    private static final Logger log = LoggerFactory.getLogger(LiveboardV1Controller.class);

    private final NmbsRivLiveboardClient liveboardClient;
    private final StationsDao stationsDao;
    private final LoadingCache<LiveboardRequest, DataRoot> cache;
    private final LiveboardLoader loader;

    @Autowired
    public LiveboardV1Controller(NmbsRivLiveboardClient liveboardClient, StationsDao stationsDao) {
        log.info("Initializing Liveboard V1 controller");
        this.liveboardClient = liveboardClient;
        this.stationsDao = stationsDao;
        this.loader = new LiveboardLoader();
        this.cache = CacheBuilder.newBuilder().expireAfterWrite(1, TimeUnit.MINUTES).build(loader);
    }

    /**
     * Gets the liveboard for a specific station.
     *
     * @param id          the station ID
     * @param stationName alternative station name parameter
     * @param date        the date for the liveboard (ddmmyy)
     * @param time        the time for the liveboard (hhmm)
     * @param arrdep      departure or arrival mode
     * @param lang        the language for response data
     * @param format      the response format
     * @return the liveboard search result
     */
    @GET
    @Path("/liveboard")
    public Response getLiveboard(
            @QueryParam("id") String id,
            @QueryParam("station") String stationName,
            @QueryParam("date") String date,
            @QueryParam("time") String time,
            @QueryParam("arrdep") @DefaultValue("departure") String arrdep,
            @QueryParam("lang") @DefaultValue("en") String lang,
            @QueryParam("format") @DefaultValue("xml") String format) throws Exception {

        TimeSelection timeSelection = RequestParser.parseV1TimeSelection(arrdep);
        Language language = RequestParser.parseLanguage(lang);
        Format outputFormat = RequestParser.parseFormat(format);
        log.debug("Fetching liveboard for station {}", Objects.requireNonNullElse(id, stationName));

        Station dbStation = findStation(id, stationName);

        // Parse date and time
        LocalDateTime dateTime = parseDateTime(date, time);

        // Create liveboard request
        LiveboardRequest request = new LiveboardRequest(dbStation, dateTime, timeSelection, language);
        DataRoot result = cache.get(request);

        // Convert to V1 format
        return v1Response(result, outputFormat);
    }

    private Station findStation(String id, String stationName) throws BadRequestException {
        // Resolve station ID - prefer 'id' parameter, fall back to 'station'
        String searchInput = (id != null && !id.isEmpty()) ? id : stationName;
        if (searchInput == null || searchInput.isEmpty()) {
            throw new BadRequestException("Missing required parameter: id / station", "id");
        }

        // Resolve station from database
        Station dbStation = stationsDao.getStationFromId(searchInput);
        if (dbStation != null) {
            return dbStation;
        }

        List<Station> stations = stationsDao.getStations(searchInput);
        if (!stations.isEmpty()) {
            dbStation = stations.getFirst();
        }

        if (dbStation == null) {
            throw new RequestedStopNotFoundException(id != null ? "id" : "station", searchInput);
        }
        return dbStation;
    }

    private final class LiveboardLoader extends CacheLoader<LiveboardRequest, DataRoot> {

        public DataRoot load(LiveboardRequest request) throws ExecutionException {
            log.debug("Loading fresh liveboard data for station {}", request.station().getIrailId());
            try {
                // Fetch liveboard data
                LiveboardSearchResult liveboardResult = LiveboardV1Controller.this.liveboardClient.getLiveboard(request);
                log.debug("Found {} entries for liveboard at station {}",
                        liveboardResult.getStops().size(), request.station().getIrailId());
                return LiveboardV1Converter.convert(request, liveboardResult);
            } catch (Exception exception) {
                log.error("Error fetching liveboard for station {}: {}", request.station().getIrailId(), exception.getMessage(), exception);
                throw exception;
            }
        }

    }

}
