package be.irail.api;

import com.codahale.metrics.health.HealthCheckRegistry;
import io.dropwizard.metrics.servlets.HealthCheckServlet;
import jakarta.servlet.annotation.WebListener;

import static be.irail.api.config.HealthCheck.HEALTHCHECKREGISTRY;

@WebListener
public class HealthCheckContextListener extends HealthCheckServlet.ContextListener {

    @Override
    protected HealthCheckRegistry getHealthCheckRegistry() {
        return HEALTHCHECKREGISTRY;
    }

}
