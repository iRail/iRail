package be.irail.api.dto;

/**
 * Supported languages for iRail API responses and station names.
 */
public enum Language {
    /** English */
    EN("en"),
    /** Dutch */
    NL("nl"),
    /** French */
    FR("fr"),
    /** German */
    DE("de");

    private final String value;

    Language(String value) {
        this.value = value;
    }

    /**
     * Gets the ISO 639-1 code of the language.
     * @return the language code
     */
    public String getValue() {
        return value;
    }

    /**
     * Finds a Language enum by its string value (case-insensitive).
     *
     * @param text the language code to look up
     * @return the matching Language, or null if not found
     */
    public static Language fromString(String text) {
        for (Language l : Language.values()) {
            if (l.value.equalsIgnoreCase(text)) {
                return l;
            }
        }
        return null;
    }
}
