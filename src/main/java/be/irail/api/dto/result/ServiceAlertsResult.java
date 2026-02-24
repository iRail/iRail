package be.irail.api.dto.result;

import be.irail.api.dto.Message;
import java.util.List;

/**
 * Result of a service alerts search.
 * Contains a list of currently active messages and disturbances.
 */
public class ServiceAlertsResult {
    private final List<Message> alerts;

    /**
     * Constructs a new ServiceAlertsResult.
     *
     * @param alerts the list of active alert messages
     */
    public ServiceAlertsResult(List<Message> alerts) {
        this.alerts = alerts;
    }

    /**
     * Gets the list of active alert messages.
     * @return the list of alerts
     */
    public List<Message> getAlerts() {
        return alerts;
    }
}
