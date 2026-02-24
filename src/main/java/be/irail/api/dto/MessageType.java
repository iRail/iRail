package be.irail.api.dto;

/**
 * Enum representing the category of a service alert or informational message.
 */
public enum MessageType {
    /** Planned engineering works */
    WORKS,
    /** Unplanned trouble or disturbances */
    TROUBLE,
    /** General information */
    INFO
}
