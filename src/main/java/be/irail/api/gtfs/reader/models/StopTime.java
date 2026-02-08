package be.irail.api.gtfs.reader.models;

import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.util.HashMap;
import java.util.Map;

/**
 * Represents a GTFS Stop Time.
 */
public record StopTime(String tripId, int arrivalTime, int departureTime, String stopId, int stopSequence,
                       String stopHeadsign, int pickupType, int dropOffType,
                       Map<Integer, String> stopIdOverridesByServiceId) {
    private static final Logger log = LogManager.getLogger(StopTime.class);

    public StopTime(String tripId, int arrivalTime, int departureTime, String stopId, int stopSequence, String stopHeadsign, int pickupType, int dropOffType) {
        this(tripId, arrivalTime, departureTime, stopId, stopSequence, stopHeadsign, pickupType, dropOffType, new HashMap<>());
    }

    public void addOverride(Integer serviceId, String stopId) {
        String existingValue = stopIdOverridesByServiceId.put(serviceId, stopId);
        if (existingValue != null) {
            log.warn("Multiple overrides for service ID {}, already stored {} when discovering {}", serviceId, existingValue, stopId);
        }
        //  if (stopIdOverridesByServiceId.size() > 1) {
        //      log.trace("Multiple stop IDs on stop_time for trip {}, stop {}, current count {}", tripId, stopId, stopIdOverridesByServiceId.size());
        //  }
    }
}
