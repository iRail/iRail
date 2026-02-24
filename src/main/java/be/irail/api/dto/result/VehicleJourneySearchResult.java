package be.irail.api.dto.result;

import be.irail.api.dto.DepartureAndArrival;
import be.irail.api.dto.DepartureOrArrival;
import be.irail.api.dto.Message;
import be.irail.api.dto.Vehicle;
import java.util.List;

/**
 * Result of a search for a specific vehicle journey.
 * Contains the vehicle information, the sequence of stops, and any service alerts for the journey.
 */
public class VehicleJourneySearchResult {
    private final Vehicle vehicle;
    private final List<DepartureAndArrival> stops;
    private final List<Message> alerts;

    /**
     * Constructs a new VehicleJourneySearchResult.
     *
     * @param vehicle the vehicle performing the journey
     * @param stops the sequence of stops in the journey
     * @param alerts the list of active service alerts for the journey
     */
    public VehicleJourneySearchResult(Vehicle vehicle, List<DepartureAndArrival> stops, List<Message> alerts) {
        this.vehicle = vehicle;
        this.stops = stops;
        this.alerts = alerts;
    }

    /**
     * Gets the vehicle associated with this journey.
     * @return the vehicle
     */
    public Vehicle getVehicle() {
        return vehicle;
    }

    /**
     * Gets the sequence of stops (departure/arrival pairs) for this journey.
     * @return the list of stops
     */
    public List<DepartureAndArrival> getStops() {
        return stops;
    }

    /**
     * Gets the departure information for a specific stop in the journey.
     *
     * @param index the stop index (0-based)
     * @return the departure stop, or null if the index is invalid
     */
    public DepartureOrArrival getDeparture(int index) {
        if (index >= 0 && index < stops.size()) {
            return stops.get(index).getDeparture();
        }
        return null;
    }

    /**
     * Gets the arrival information for a specific stop in the journey.
     *
     * @param index the stop index (0-based)
     * @return the arrival stop, or null if the index is invalid
     */
    public DepartureOrArrival getArrival(int index) {
        if (index >= 0 && index < stops.size()) {
            return stops.get(index).getArrival();
        }
        return null;
    }

    /**
     * Gets the service alerts that affect this journey.
     * @return the list of alerts
     */
    public List<Message> getAlerts() {
        return alerts;
    }
}
