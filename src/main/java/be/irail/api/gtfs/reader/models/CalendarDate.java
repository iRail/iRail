package be.irail.api.gtfs.reader.models;

import java.time.LocalDate;

/**
 * Represents a GTFS Calendar Date.
 */
public record CalendarDate(String serviceId, LocalDate date) {
}
