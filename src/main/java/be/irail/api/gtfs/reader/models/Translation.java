package be.irail.api.gtfs.reader.models;

/**
 * Represents a GTFS Translation.
 */
public record Translation(String tableName, String fieldName, String language, String translation, String recordId, String recordSubId, String fieldValue) {
}
