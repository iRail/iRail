package be.irail.api.dto;

import be.irail.api.db.OccupancyReport;

/**
 * Container for different sources of occupancy information for a specific vehicle stop.
 * Holds both official data (from NMBS/SNCB) and crowd-sourced data (from Spitsgids).
 */
public class OccupancyInfo {
    public static final OccupancyInfo UNKNOWN = new OccupancyInfo(null, null);
    private OccupancyLevelDTO officialLevel;
    private OccupancyLevelDTO spitsgidsLevel;

    /**
     * Constructs a new OccupancyInfo with official and crowd-sourced levels.
     *
     * @param officialLevel  the official occupancy level
     * @param spitsgidsLevel the crowd-sourced occupancy level
     */
    public OccupancyInfo(OccupancyReport.OccupancyLevel officialLevel, OccupancyReport.OccupancyLevel spitsgidsLevel) {
        this.officialLevel = OccupancyLevelDTO.fromLevel(officialLevel);
        this.spitsgidsLevel = OccupancyLevelDTO.fromLevel(spitsgidsLevel);
    }

    /**
     * Gets the official occupancy level reported by the carrier.
     *
     * @return the official occupancy level
     */
    public OccupancyLevelDTO getOfficialLevel() {
        return this.officialLevel;
    }

    /**
     * Sets the official occupancy level.
     *
     * @param officialLevel the level to set
     */
    public void setOfficialLevel(OccupancyLevelDTO officialLevel) {
        this.officialLevel = officialLevel;
    }

    /**
     * Gets the crowd-sourced occupancy level from Spitsgids.
     *
     * @return the Spitsgids occupancy level
     */
    public OccupancyLevelDTO getSpitsgidsLevel() {
        return this.spitsgidsLevel;
    }

    /**
     * Sets the crowd-sourced occupancy level.
     *
     * @param spitsgidsLevel the level to set
     */
    public void setSpitsgidsLevel(OccupancyLevelDTO spitsgidsLevel) {
        this.spitsgidsLevel = spitsgidsLevel;
    }
}
