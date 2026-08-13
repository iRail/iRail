package be.irail.api.gtfs.reader.models;

import be.irail.api.dto.Language;

import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Represents a GTFS Stop.
 */
public record Stop(String id, String code, String name, String desc, double lat, double lon, String zoneId, String url,
                   int locationType, String parentStation, String timezone, int wheelchairBoarding, String levelId,
                   String platformCode, Map<Language, String> localNames) {
    private static final Pattern NMBS_NUMERIC_STOP_ID = Pattern.compile("^(?:gs:nmbssncb:)?S?(\\d{7})(?:_.*)?$");

    public String getName(Language language) {
        return localNames.getOrDefault(language, name);
    }

    public String getHafasId() {
        return getHafasId(id);
    }

    /**
     * Converts both legacy and namespaced NMBS GTFS stop identifiers to their
     * seven-digit HAFAS identifier. Name-based international stop identifiers
     * cannot be converted and return {@code null}.
     */
    public static String getHafasId(String gtfsStopId) {
        if (gtfsStopId == null) {
            return null;
        }
        Matcher matcher = NMBS_NUMERIC_STOP_ID.matcher(gtfsStopId);
        return matcher.matches() ? matcher.group(1) : null;
    }
}
