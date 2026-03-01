package be.irail.api.gtfs.reader.models;

import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.Map;

/**
 * Represents a GTFS Stop Time.
 */
public record StopTime(String tripId, int arrivalOffsetSeconds, int departureOffsetSeconds, String stopId,
                       int stopSequence,
                       String stopHeadsign, PickupDropoffType pickupType, PickupDropoffType dropOffType,
                       Map<Integer, String> stopIdOverridesByServiceId) {
    private static final Logger log = LogManager.getLogger(StopTime.class);

    public StopTime(String tripId, int arrivalTime, int departureTime, String stopId, int stopSequence, String stopHeadsign,
                    PickupDropoffType pickupType, PickupDropoffType dropOffType) {
        this(tripId, arrivalTime, departureTime, stopId, stopSequence, stopHeadsign, pickupType, dropOffType, new HashMap<>());
    }

    public boolean hasScheduledPassengerExchange() {
        return pickupType() == PickupDropoffType.SCHEDULED || dropOffType() == PickupDropoffType.SCHEDULED;
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

    public LocalDateTime getDepartureTime(LocalDate date) {
        return date.atStartOfDay().plusSeconds(departureOffsetSeconds);
    }

    public LocalDateTime getArrivalTime(LocalDate date) {
        return date.atStartOfDay().plusSeconds(arrivalOffsetSeconds);
    }
}
