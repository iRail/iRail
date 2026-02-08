package be.irail.api.dto;

/**
 * Enum representing the type of a journey leg.
 */
public enum JourneyLegType {
    /** Walking between locations or platforms */
    WALKING("WALK"),
    /** A scheduled journey (typically a train) */
    JOURNEY("JNY"),
    /** A check-in event */
    CHECK_IN("CHKI");

    private final String value;

    JourneyLegType(String value) {
        this.value = value;
    }

    /**
     * Gets the string code associated with the leg type.
     * @return the leg type code
     */
    public String getValue() {
        return value;
    }
}
