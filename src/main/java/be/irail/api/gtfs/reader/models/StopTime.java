package be.irail.api.gtfs.reader.models;

import java.time.LocalDate;
import java.time.LocalDateTime;

/**
 * Represents a GTFS Stop Time.
 */
public record StopTime(String tripId, int arrivalOffsetSeconds, int departureOffsetSeconds, String stopId,
                       int stopSequence,
                       String stopHeadsign, PickupDropoffType pickupType, PickupDropoffType dropOffType
) {

    public boolean hasScheduledPassengerExchange() {
        return pickupType() == PickupDropoffType.SCHEDULED || dropOffType() == PickupDropoffType.SCHEDULED;
    }


    public LocalDateTime getDepartureTime(LocalDate date) {
        return date.atStartOfDay().plusSeconds(departureOffsetSeconds);
    }

    public LocalDateTime getArrivalTime(LocalDate date) {
        return date.atStartOfDay().plusSeconds(arrivalOffsetSeconds);
    }
}
