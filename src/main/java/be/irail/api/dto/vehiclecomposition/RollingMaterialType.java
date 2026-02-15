package be.irail.api.dto.vehiclecomposition;

/**
 * Represents the type of rolling stock material.
 * Includes both a parent category and a specific subtype, as well as orientation.
 */
public class RollingMaterialType {
    private final String parentType;
    private final String subType;
    private RollingMaterialOrientation orientation = RollingMaterialOrientation.LEFT;

    /**
     * Constructs a new RollingMaterialType.
     *
     * @param parentType the parent type (e.g., "M7", "AM80")
     * @param subType the specific subtype (e.g., "M7BUH", "AM80_c")
     */
    public RollingMaterialType(String parentType, String subType) {
        this.parentType = parentType;
        this.subType = subType;
    }

    /**
     * Gets the parent category of the material.
     * @return the parent type
     */
    public String getParentType() {
        return parentType;
    }

    /**
     * Gets the specific subtype of the material.
     * @return the subtype
     */
    public String getSubType() {
        return subType;
    }

    /**
     * Gets the physical orientation of the unit.
     * @return the orientation
     */
    public RollingMaterialOrientation getOrientation() {
        return orientation;
    }

    /**
     * Sets the physical orientation of the unit.
     * @param value the new orientation
     * @return this instance for chaining
     */
    public RollingMaterialType setOrientation(RollingMaterialOrientation value) {
        this.orientation = value;
        return this;
    }

    @Override
    public String toString() {
        return "RollingMaterialType '" + parentType + '\'' +
                ", '" + subType + '\'' +
                ", " + orientation;
    }
}
