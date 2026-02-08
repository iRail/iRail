package be.irail.api.dto.result;

import be.irail.api.dto.Vehicle;
import be.irail.api.dto.vehiclecomposition.TrainComposition;
import java.util.List;

/**
 * Result of a vehicle composition search.
 * Contains composition data for each segment of a train ride.
 */
public class VehicleCompositionSearchResult {
    private final List<TrainComposition> segments;
    private final Vehicle vehicle;

    /**
     * Constructs a new VehicleCompositionSearchResult.
     *
     * @param vehicle the vehicle for which the composition was requested
     * @param segments the list of compositions for each ride segment
     */
    public VehicleCompositionSearchResult(Vehicle vehicle, List<TrainComposition> segments) {
        this.segments = segments;
        this.vehicle = vehicle;
    }

    /**
     * Gets the list of compositions for each segment of the train ride.
     * @return the list of train compositions
     */
    public List<TrainComposition> getSegments() {
        return this.segments;
    }

    /**
     * Gets the vehicle associated with this composition search.
     * @return the vehicle
     */
    public Vehicle getVehicle() {
        return this.vehicle;
    }
}
