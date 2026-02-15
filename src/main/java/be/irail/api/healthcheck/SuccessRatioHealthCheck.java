package be.irail.api.healthcheck;

import com.codahale.metrics.health.HealthCheck;

public class SuccessRatioHealthCheck extends HealthCheck {
    private final RatioGauge successRatioGauge;
    private final float threshold;

    public SuccessRatioHealthCheck(RatioGauge successRatioGauge, float threshold) {
        this.successRatioGauge = successRatioGauge;
        this.threshold = threshold;
    }

    @Override
    protected Result check() throws Exception {
        return successRatioGauge.getValue() > threshold || successRatioGauge.getDiff() < 0.1
                ? Result.healthy("Success rate %.02f%% above threshold of %.02f%%", 100 * successRatioGauge.getValue(), 100 * threshold)
                : Result.unhealthy("Success rate %.02f%% below threshold of %.02f%%".formatted(100 * successRatioGauge.getValue(), 100 * threshold));
    }
}
