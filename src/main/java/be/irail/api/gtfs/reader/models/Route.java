package be.irail.api.gtfs.reader.models;

/**
 * Represents a GTFS Route.
 */
public record Route(String id, String agencyId, String shortName, String longName, String desc, int type) {
}
