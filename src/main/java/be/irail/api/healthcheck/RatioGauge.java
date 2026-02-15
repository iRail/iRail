package be.irail.api.healthcheck;

import com.codahale.metrics.Gauge;
import com.codahale.metrics.Meter;

public class RatioGauge implements Gauge<Float> {
    private final Meter succesMeter;
    private final Meter totalMeter;
    private double total;
    private double success;

    public RatioGauge(Meter succesMeter, Meter totalMeter) {
        this.succesMeter = succesMeter;
        this.totalMeter = totalMeter;
    }

    @Override
    public Float getValue() {
        total = totalMeter.getOneMinuteRate();
        if (total == 0) {
            return 100f;
        }
        success = succesMeter.getOneMinuteRate();
        return Math.round(100 * success / total) / 100f;
    }

    public double getDiff() {
        return total - success;
    }

    public double getTotalCount() {
        return total;
    }

    public double getSuccessCount() {
        return success;
    }
}
