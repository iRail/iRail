package be.irail.api.gtfs.reader.models;

import java.time.OffsetDateTime;

/**
 * Represents a delay for a specific trip and stop in the GTFS-Realtime feed.
 */
public record GtfsRtDelay(
        java.time.LocalDate startDate, String tripId,
        String stopId,
        String parentStopId,
        int arrivalDelay,
        int departureDelay,
        OffsetDateTime timestamp
) {}
