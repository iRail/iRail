package be.irail.api.dto;

/**
 * Container for different sources of occupancy information for a specific vehicle stop.
 * Holds both official data (from NMBS/SNCB) and crowd-sourced data (from Spitsgids).
 */
public class OccupancyInfo {
    private OccupancyLevel officialLevel;
    private OccupancyLevel spitsgidsLevel;

    /**
     * Constructs a new OccupancyInfo with official and crowd-sourced levels.
     *
     * @param officialLevel the official occupancy level
     * @param spitsgidsLevel the crowd-sourced occupancy level
     */
    public OccupancyInfo(OccupancyLevel officialLevel, OccupancyLevel spitsgidsLevel) {
        this.officialLevel = officialLevel;
        this.spitsgidsLevel = spitsgidsLevel;
    }

    /**
     * Gets the official occupancy level reported by the carrier.
     * @return the official occupancy level
     */
    public OccupancyLevel getOfficialLevel() {
        return this.officialLevel;
    }

    /**
     * Sets the official occupancy level.
     * @param officialLevel the level to set
     */
    public void setOfficialLevel(OccupancyLevel officialLevel) {
        this.officialLevel = officialLevel;
    }

    /**
     * Gets the crowd-sourced occupancy level from Spitsgids.
     * @return the Spitsgids occupancy level
     */
    public OccupancyLevel getSpitsgidsLevel() {
        return this.spitsgidsLevel;
    }

    /**
     * Sets the crowd-sourced occupancy level.
     * @param spitsgidsLevel the level to set
     */
    public void setSpitsgidsLevel(OccupancyLevel spitsgidsLevel) {
        this.spitsgidsLevel = spitsgidsLevel;
    }
}
