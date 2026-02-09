package be.irail.api.config;

import be.irail.api.controllers.StatusController;
import be.irail.api.controllers.V2Controller;
import be.irail.api.controllers.v1.*;
import be.irail.api.exception.IrailExceptionMapper;
import org.glassfish.jersey.server.ResourceConfig;
import org.springframework.context.annotation.Configuration;

/**
 * Configuration class for Jersey (JAX-RS).
 * Registers all API controllers and configures the JAX-RS environment.
 */
@Configuration
public class JerseyConfig extends ResourceConfig {

    /**
     * Constructs a new JerseyConfig and registers all resource classes.
     */
    public JerseyConfig() {
        // V1 endpoints
        register(StationsV1Controller.class);
        register(LiveboardV1Controller.class);
        register(JourneyPlanningV1Controller.class);
        register(DatedVehicleJourneyV1Controller.class);
        register(ServiceAlertsV1Controller.class);
        register(CompositionV1Controller.class);
        register(LogsV1Controller.class);

        register(V2Controller.class);
        register(StatusController.class);
        register(IrailExceptionMapper.class);
    }
}
