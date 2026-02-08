package be.irail.api.controllers.v1;

import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.result.ServiceAlertsResult;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.legacy.DataRoot;
import be.irail.api.legacy.ServiceAlertsV1Converter;
import be.irail.api.riv.NmbsRssDisturbancesClient;
import be.irail.api.riv.requests.ServiceAlertsRequest;
import be.irail.api.util.RequestParser;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

import java.util.concurrent.TimeUnit;

/**
 * Controller for V1 Service Alerts (disturbances) API endpoint.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class ServiceAlertsV1Controller extends V1Controller {

    private static final Logger log = LogManager.getLogger(ServiceAlertsV1Controller.class);

    private final NmbsRssDisturbancesClient serviceAlertsClient;
    private static final Cache<ServiceAlertsRequest, DataRoot> cache = CacheBuilder.newBuilder()
            .maximumSize(12)
            .expireAfterWrite(3, TimeUnit.MINUTES)
            .build();
    private static final Cache<ServiceAlertsRequest, DataRoot> backupCache = CacheBuilder.newBuilder()
            .maximumSize(12)
            .expireAfterWrite(12, TimeUnit.HOURS)
            .build();

    @Autowired
    public ServiceAlertsV1Controller(NmbsRssDisturbancesClient serviceAlertsClient) {
        this.serviceAlertsClient = serviceAlertsClient;
    }

    /**
     * Gets current service alerts/disturbances.
     *
     * @param lang   the language for response data
     * @param format the response format
     * @return the service alerts result
     */
    @GET
    @Path("/disturbances")
    public Response getServiceAlerts(
            @QueryParam("lang") @DefaultValue("en") String lang,
            @QueryParam("format") @DefaultValue("xml") String format) {
        Language language = RequestParser.parseLanguage(lang);
        Format outputFormat = RequestParser.parseFormat(format);
        ServiceAlertsRequest request = new ServiceAlertsRequest(language);
        try {
            // Fetch service alerts
            DataRoot dataRoot = cache.get(request, () -> getDataRoot(request));
            backupCache.put(request, dataRoot);

            return v1Response(dataRoot, outputFormat);
        } catch (Exception exception) {
            return tryReturnBackupDataFromLongTermCache(exception, request, outputFormat);
        }
    }

    private Response tryReturnBackupDataFromLongTermCache(Exception exception, ServiceAlertsRequest request, Format outputFormat) {
        DataRoot backupData = backupCache.getIfPresent(request);
        if (backupData != null) {
            try {
                return v1Response(backupData, outputFormat);
            } catch (Exception e) {
                log.error("Error converting backup data to V1 format: {}", e.getMessage(), e);
                throw new InternalProcessingException("Error converting backup data to V1 format: {}");
            }
        }
        log.error("Error fetching service alerts: {}", exception.getMessage(), exception);
        throw new InternalProcessingException("Error fetching service alerts: " + exception.getMessage(), exception);
    }

    private DataRoot getDataRoot(ServiceAlertsRequest request) {
        ServiceAlertsResult serviceAlertsResult = serviceAlertsClient.getServiceAlerts(request);

        // Convert to V1 format
        return ServiceAlertsV1Converter.convert(serviceAlertsResult);
    }
}
