package be.irail.api.dto;

/**
 * Supported response formats for the iRail API.
 */
public enum Format {
    /** JSON format */
    JSON("json"),
    /** XML format (legacy default) */
    XML("xml");

    private final String value;

    Format(String value) {
        this.value = value;
    }

    /**
     * Gets the string representation of the format.
     * @return the format string
     */
    public String getValue() {
        return value;
    }

    /**
     * Finds a Format enum by its string value (case-insensitive).
     *
     * @param text the format string to look up
     * @return the matching Format, or null if not found
     */
    public static Format fromString(String text) {
        for (Format f : Format.values()) {
            if (f.value.equalsIgnoreCase(text)) {
                return f;
            }
        }
        return null;
    }
}
