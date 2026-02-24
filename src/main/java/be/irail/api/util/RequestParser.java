package be.irail.api.util;

import be.irail.api.dto.Format;
import be.irail.api.dto.Language;
import be.irail.api.dto.TimeSelection;

/**
 * Utility class for parsing and validating iRail API request parameters.
 * Provides methods for mapping raw string parameters to strongly-typed enums.
 */
public class RequestParser {

    /**
     * Parses a language string into a Language enum.
     * Defaults to English if the language is unknown or null.
     *
     * @param lang the raw language string
     * @return the corresponding Language enum
     */
    public static Language parseLanguage(String lang) {
        if (lang == null) return Language.EN;
        Language l = Language.fromString(lang);
        return l != null ? l : Language.EN;
    }

    /**
     * Parses a format string into a Format enum.
     * Defaults to XML if the format is unknown or null.
     *
     * @param format the raw format string
     * @return the corresponding Format enum
     */
    public static Format parseFormat(String format) {
        if (format == null) return Format.XML;
        Format f = Format.fromString(format);
        return f != null ? f : Format.XML;
    }

    /**
     * Parses a time selection parameter using V1 (lenient) logic.
     * Any value starting with 'a' is considered ARRIVAL, otherwise DEPARTURE.
     *
     * @param value the raw parameter value
     * @return the corresponding TimeSelection enum
     */
    public static TimeSelection parseV1TimeSelection(String value) {
        if (value == null || value.isEmpty()) return TimeSelection.DEPARTURE;
        if (value.toLowerCase().startsWith("a")) {
            return TimeSelection.ARRIVAL;
        }
        return TimeSelection.DEPARTURE;
    }

    /**
     * Parses a time selection parameter using V2 (strict) logic.
     * Accepts 'arrival' or 'departure' exactly (case-insensitive via enum).
     *
     * @param value the raw parameter value
     * @return the corresponding TimeSelection enum
     * @throws IllegalArgumentException if the value is invalid
     */
    public static TimeSelection parseV2TimeSelection(String value) {
        if (value == null) return TimeSelection.DEPARTURE;
        TimeSelection ts = TimeSelection.fromString(value);
        if (ts != null) return ts;
        throw new IllegalArgumentException("The provided time mode selection '" + value + "' is invalid. Should be one of 'departure', 'arrival'.");
    }
}
