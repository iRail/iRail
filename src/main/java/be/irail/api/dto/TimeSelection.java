package be.irail.api.dto;

/**
 * Enum representing whether a time-based search is for a departure or an arrival.
 */
public enum TimeSelection {
    /** Search for departures occurring at or after the specified time */
    DEPARTURE("departure"),
    /** Search for arrivals occurring at or before the specified time */
    ARRIVAL("arrival");

    private final String value;

    TimeSelection(String value) {
        this.value = value;
    }

    /**
     * Gets the string value of the time selection mode.
     * @return the mode string
     */
    public String getValue() {
        return value;
    }

    /**
     * Finds a TimeSelection enum by its string value (case-insensitive).
     *
     * @param text the mode string to look up
     * @return the matching TimeSelection, or null if not found
     */
    public static TimeSelection fromString(String text) {
        for (TimeSelection ts : TimeSelection.values()) {
            if (ts.value.equalsIgnoreCase(text)) {
                return ts;
            }
        }
        return null;
    }
}
