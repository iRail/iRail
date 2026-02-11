package be.irail.api;

import be.irail.api.config.Metrics;
import com.codahale.metrics.MetricRegistry;
import io.dropwizard.metrics.servlets.MetricsServlet.ContextListener;
import jakarta.servlet.annotation.WebListener;

@WebListener
public class DropwizardContextListener extends ContextListener {

    public static final MetricRegistry METRIC_REGISTRY = Metrics.getRegistry();

    @Override
    protected MetricRegistry getMetricRegistry() {
        return METRIC_REGISTRY;
    }

}
