package be.irail.api.config;

import com.codahale.metrics.MetricRegistry;

public class Metrics {

    private static final MetricRegistry registry = new MetricRegistry();

    public static MetricRegistry getRegistry() {
        return registry;
    }
}
