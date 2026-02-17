package be.irail.api.gtfs.reader;

import java.time.LocalDate;

public record DatedTripId(String tripId, LocalDate date) {
}
