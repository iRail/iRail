package be.irail.api.gtfs.reader.models;

/**
 * Represents a GTFS Transfer.
 */
public record Transfer(String fromStopId, String toStopId, int transferType, int minTransferTime) {
}
