package be.irail.api.dto;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * Enum representing the occupancy level of a vehicle.
 * Maps between iRail URIs, NMBS internal levels, and GTFS concepts.
 */
public enum OccupancyLevel {
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

    private static final Logger LOGGER = LoggerFactory.getLogger(OccupancyLevel.class);
    private final String uri;

    OccupancyLevel(String uri) {
        this.uri = uri;
    }

    /**
     * Gets the iRail URI for this occupancy level.
     * @return the level URI
     */
    public String getUri() {
        return uri;
    }

    /**
     * Finds an OccupancyLevel by its iRail URI.
     *
     * @param uri the URI to look up
     * @return the matching OccupancyLevel, or UNKNOWN if not found
     */
    public static OccupancyLevel fromUri(String uri) {
        for (OccupancyLevel level : OccupancyLevel.values()) {
            if (level.uri.equals(uri)) {
                return level;
            }
        }
        LOGGER.error("Unknown occupancy level uri {}", uri);
        return UNKNOWN;
    }

    /**
     * Converts an internal NMBS occupancy level (1-4) to an OccupancyLevel enum.
     *
     * @param level the NMBS level code
     * @return the corresponding OccupancyLevel
     */
    public static OccupancyLevel fromNmbsLevel(int level) {
        switch (level) {
            case 1:
                return LOW;
            case 2:
                return MEDIUM;
            case 3:
            case 4:
                return HIGH;
            default:
                LOGGER.error("Unknown NMBS occupancy level {}", level);
                return UNKNOWN;
        }
    }

    /**
     * Gets the integer value used in legacy systems (1-3).
     *
     * @return the integer value
     * @throws IllegalArgumentException if the level is UNKNOWN
     */
    public int getIntValue() {
        switch (this) {
            case LOW:
                return 1;
            case MEDIUM:
                return 2;
            case HIGH:
                return 3;
            case UNKNOWN:
            default:
                throw new IllegalArgumentException();
        }
    }

    /**
     * Converts a legacy integer value (1-3) to an OccupancyLevel enum.
     *
     * @param value the integer value
     * @return the corresponding OccupancyLevel
     * @throws IllegalArgumentException if the value is invalid
     */
    public static OccupancyLevel fromIntValue(int value) {
        switch (value) {
            case 1:
                return LOW;
            case 2:
                return MEDIUM;
            case 3:
                return HIGH;
            default:
                throw new IllegalArgumentException();
        }
    }
}
