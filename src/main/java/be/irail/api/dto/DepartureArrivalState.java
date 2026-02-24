package be.irail.api.dto;

/**
 * Represents the real-time state of a vehicle relative to a stop.
 */
public enum DepartureArrivalState {
    /** The vehicle is currently approaching the station */
    APPROACHING,
    /** The vehicle is currently halting at the station */
    HALTING,
    /** The vehicle has already left the station */
    REPORTED;

    /**
     * Checks if the vehicle has already arrived at the station (either halting or already left).
     * @return true if the vehicle has arrived
     */
    public boolean hasArrived() {
        return this == HALTING || this == REPORTED;
    }

    /**
     * Checks if the vehicle has already left the station.
     * @return true if the vehicle has left
     */
    public boolean hasLeft() {
        return this == REPORTED;
    }
}
