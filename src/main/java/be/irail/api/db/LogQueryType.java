package be.irail.api.db;

/**
 * Enum representing the different types of queries that can be logged.
 */
public enum LogQueryType {
    /** Request for a station liveboard */
    LIVEBOARD("Liveboard"),
    /** Request for journey planning/connections */
    JOURNEYPLANNING("JourneyPlanning"),
    /** Request for service alerts or disturbances */
    SERVICEALERTS("ServiceAlerts"),
    /** Request for a specific dated vehicle journey */
    DATEDVEHICLEJOURNEY("DatedVehicleJny"),
    /** Request for the list of stations */
    STATIONS("Stations"),
    /** Request for vehicle composition information */
    VEHICLECOMPOSITION("Composition");

    private final String value;

    LogQueryType(String value) {
        this.value = value;
    }

    /**
     * Gets the string identifier for the query type.
     * @return the query type identifier
     */
    public String getValue() {
        return value;
    }
}
