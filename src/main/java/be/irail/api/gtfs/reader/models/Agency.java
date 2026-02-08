package be.irail.api.gtfs.reader.models;

/**
 * Represents a GTFS Agency.
 */
public record Agency(String id, String name, String url, String timezone, String lang, String phone, String fareUrl, String email) {
}
