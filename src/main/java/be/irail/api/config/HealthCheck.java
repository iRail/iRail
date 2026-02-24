package be.irail.api.config;

import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.healthcheck.SuccessRatioHealthCheck;
import com.codahale.metrics.health.HealthCheckRegistry;
import org.jvnet.hk2.annotations.Service;
import org.springframework.scheduling.annotation.Scheduled;

import static be.irail.api.config.Metrics.*;

@Service
public class HealthCheck {

    private static final float LIVEBOARD_SUCCESS_THRESHOLD = 0.9f;
    private static final float JP_SUCCESS_THRESHOLD = 0.7f;
    private static final float VEHICLE_SUCCESS_THRESHOLD = 0.5f;
    private static final float COMPOSITION_SUCCESS_THRESHOLD = 0.5f;

    public static final HealthCheckRegistry HEALTHCHECKREGISTRY = new HealthCheckRegistry();

    static {
        HEALTHCHECKREGISTRY.register("liveboardV1", new SuccessRatioHealthCheck(V1_LIVEBOARD_GAUGE, LIVEBOARD_SUCCESS_THRESHOLD));
        HEALTHCHECKREGISTRY.register("journeyPlanningV1", new SuccessRatioHealthCheck(V1_JOURNEYPLANNING_GAUGE, JP_SUCCESS_THRESHOLD));
        HEALTHCHECKREGISTRY.register("vehicleV1", new SuccessRatioHealthCheck(V1_VEHICLE_GAUGE, VEHICLE_SUCCESS_THRESHOLD));
        HEALTHCHECKREGISTRY.register("compositionV1", new SuccessRatioHealthCheck(V1_COMPOSITION_GAUGE, COMPOSITION_SUCCESS_THRESHOLD));
        HEALTHCHECKREGISTRY.register("gtfs", new com.codahale.metrics.health.HealthCheck() {
            @Override
            protected Result check() throws Exception {
                return GtfsInMemoryDao.getInstance() != null ? Result.healthy("GTFS data loaded") : Result.unhealthy("GTFS data not loaded");
            }
        });

    }

    @Scheduled(fixedRate = 15000)
    public void runHealthChecks() {
        HEALTHCHECKREGISTRY.runHealthChecks();
    }

}
