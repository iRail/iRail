package be.irail.api.gtfs.reader.models;

import be.irail.api.dto.Language;

import java.util.Map;

/**
 * Represents a GTFS Stop.
 */
public record Stop(String id, String code, String name, String desc, double lat, double lon, String zoneId, String url,
                   int locationType, String parentStation, String timezone, int wheelchairBoarding, String levelId,
                   String platformCode, Map<Language, String> localNames) {
    public String getName(Language language) {
        return localNames.getOrDefault(language, name);
    }
}
