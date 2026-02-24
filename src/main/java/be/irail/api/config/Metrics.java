package be.irail.api.config;

import be.irail.api.healthcheck.RatioGauge;
import com.codahale.metrics.Meter;
import com.codahale.metrics.MetricRegistry;

public class Metrics {

    private static final MetricRegistry registry = new MetricRegistry();

    public static final Meter V1_LIVEBOARD_REQUEST_METER = getRegistry().meter("Requests /v1/liveboard");
    public static final Meter V1_LIVEBOARD_SUCCESS_REQUEST_METER = getRegistry().meter("Requests /v1/liveboard, Successful");

    public static final Meter V1_JOURNEYPLANNING_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/connections");
    public static final Meter V1_JOURNEYPLANNING_SUCCESS_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/connections, Successful");

    public static final Meter V1_VEHICLE_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/vehicle");
    public static final Meter V1_VEHICLE_SUCCESS_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/vehicle, Successful");

    public static final Meter V1_COMPOSITION_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/composition");
    public static final Meter V1_COMPOSITION_SUCCESS_REQUEST_METER = Metrics.getRegistry().meter("Requests /v1/composition, Successful");

    public static final RatioGauge V1_LIVEBOARD_GAUGE = Metrics.getRegistry().register(
            "Requests /v1/liveboard, success ratio",
            new RatioGauge(V1_LIVEBOARD_SUCCESS_REQUEST_METER, V1_LIVEBOARD_REQUEST_METER)
    );
    public static final RatioGauge V1_JOURNEYPLANNING_GAUGE = Metrics.getRegistry().register(
            "Requests /v1/connections, success ratio",
            new RatioGauge(V1_JOURNEYPLANNING_SUCCESS_REQUEST_METER, V1_JOURNEYPLANNING_REQUEST_METER)
    );
    public static final RatioGauge V1_VEHICLE_GAUGE = Metrics.getRegistry().register(
            "Requests /v1/vehicle, success ratio",
            new RatioGauge(V1_VEHICLE_SUCCESS_REQUEST_METER, V1_VEHICLE_REQUEST_METER)
    );
    public static final RatioGauge V1_COMPOSITION_GAUGE = Metrics.getRegistry().register(
            "Requests /v1/composition, success ratio",
            new RatioGauge(V1_COMPOSITION_SUCCESS_REQUEST_METER, V1_COMPOSITION_REQUEST_METER)
    );


    public static MetricRegistry getRegistry() {
        return registry;
    }

    private Metrics() {

    }
}
