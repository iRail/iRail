package be.irail.api.dto;

import be.irail.api.db.OccupancyReport.OccupancyLevel;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * Enum representing the occupancy level of a vehicle.
 * Maps between iRail URIs, NMBS internal levels, and GTFS concepts.
 */
public enum OccupancyLevelDTO {
    /**
     * No data available.
     * Maps to GTFS NO_DATA_AVAILABLE.
     */
    UNKNOWN("http://api.irail.be/terms/unknown"),

    /**
     * Low occupancy.
     * Maps to NMBS/SNCB "Low" and GTFS MANY_SEATS_AVAILABLE.
     */
    LOW("http://api.irail.be/terms/low"),

    /**
     * Medium occupancy.
     * Maps to NMBS/SNCB "Medium" (Yellow) and GTFS FEW_SEATS_AVAILABLE.
     */
    MEDIUM("http://api.irail.be/terms/medium"),

    /**
     * High occupancy.
     * Maps to NMBS/SNCB "Medium" (Orange) and GTFS STANDING_ROOM_ONLY.
     */
    HIGH("http://api.irail.be/terms/high");

    private static final Logger LOGGER = LoggerFactory.getLogger(OccupancyLevelDTO.class);
    private final String uri;

    OccupancyLevelDTO(String uri) {
        this.uri = uri;
    }

    /**
     * Gets the iRail URI for this occupancy level.
     *
     * @return the level URI
     */
    public String getUri() {
        return uri;
    }

    public static OccupancyLevelDTO fromLevel(OccupancyLevel dbLevel) {
        if (dbLevel == null) {
            return OccupancyLevelDTO.UNKNOWN;
        }
        return switch (dbLevel) {
            case LOW -> OccupancyLevelDTO.LOW;
            case MEDIUM -> OccupancyLevelDTO.MEDIUM;
            case HIGH -> OccupancyLevelDTO.HIGH;
        };
    }

    public static OccupancyLevel toDbLevel(OccupancyLevelDTO dbLevel) {
        return switch (dbLevel) {
            case LOW -> OccupancyLevel.LOW;
            case MEDIUM -> OccupancyLevel.MEDIUM;
            case HIGH -> OccupancyLevel.HIGH;
            case UNKNOWN -> null;
        };
    }
}
