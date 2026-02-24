package be.irail.api.gtfs.reader.models;

/**
 * Represents a GTFS Trip.
 */
public record Trip(String id, String routeId, int serviceId, String headsign, int shortName, int directionId, String blockId) {

}
