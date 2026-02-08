package be.irail.api.dto;

import be.irail.api.exception.InternalProcessingException;

/**
 * Represents a pair of departure and arrival events at a specific stop for a vehicle.
 * This is typically used to represent a stop in a vehicle's journey where it both arrives and then departs.
 */
public class DepartureAndArrival {
    private DepartureOrArrival arrival;
    private DepartureOrArrival departure;

    /**
     * Gets the URI for this connection/departure.
     * @return the connection URI
     */
    public String getUri() {
        return this.departure != null ? this.departure.getDepartureUri() : null;
    }

    /**
     * Gets the arrival part of this stop.
     * @return the arrival information
     */
    public DepartureOrArrival getArrival() {
        return this.arrival;
    }

    /**
     * Sets the arrival part of this stop.
     * @param arrival the arrival information to set
     * @return this instance for chaining
     */
    public DepartureAndArrival setArrival(DepartureOrArrival arrival) {
        this.arrival = arrival;
        return this;
    }

    /**
     * Gets the departure part of this stop.
     * @return the departure information
     */
    public DepartureOrArrival getDeparture() {
        return this.departure;
    }

    /**
     * Sets the departure part of this stop.
     * @param departure the departure information to set
     * @return this instance for chaining
     */
    public DepartureAndArrival setDeparture(DepartureOrArrival departure) {
        this.departure = departure;
        return this;
    }

    /**
     * Gets the station associated with this stop.
     * Returns the station from departure if available, otherwise from arrival.
     *
     * @return the station
     * @throws RuntimeException if neither departure nor arrival is set
     */
    public StationDto getStation() {
        if (this.departure != null) {
            return this.departure.getStation();
        }
        if (this.arrival != null) {
            return this.arrival.getStation();
        }
        throw new InternalProcessingException("Trying to read the station from a DepartureAndArrival which neither has a departure nor arrival");
    }
}
