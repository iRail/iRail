package be.irail.api.gtfs.reader.models;

import java.time.LocalDate;

/**
 * Represents GTFS Feed Info.
 */
public record FeedInfo(String publisherName, String publisherUrl, String lang, LocalDate startDate, LocalDate endDate, String version, String contactEmail, String contactUrl) {
}
