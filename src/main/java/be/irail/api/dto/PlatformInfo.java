package be.irail.api.dto;

/**
 * Represents information about a station platform.
 * Includes the platform designation and whether it has changed from the originally scheduled one.
 */
public class PlatformInfo {
    private final String id;
    private final String designation;
    private final boolean hasChanged;

    /**
     * Constructs a new PlatformInfo.
     *
     * @param parentStopId the identifier of the parent station stop
     * @param designation the platform designation (e.g., "4", "A")
     * @param hasChanged true if the platform has changed from the original schedule
     */
    public PlatformInfo(String parentStopId, String designation, boolean hasChanged) {
        this.id = (parentStopId != null && designation != null) ? parentStopId + "#" + designation : null;
        this.designation = designation != null ? designation : "?";
        this.hasChanged = hasChanged;
    }

    /**
     * Gets the unique identifier for this platform at this station.
     * @return the platform ID
     */
    public String getId() {
        return this.id;
    }

    /**
     * Gets the platform designation (e.g., name or number).
     * @return the platform designation
     */
    public String getDesignation() {
        return this.designation;
    }

    /**
     * Checks if the platform has changed from the originally scheduled one.
     * @return true if the platform has changed
     */
    public boolean hasChanged() {
        return this.hasChanged;
    }
}
