package be.irail.api.dto.vehiclecomposition;

import be.irail.api.db.Station;
import be.irail.api.dto.StationDto;
import be.irail.api.dto.Vehicle;
import java.util.List;
import java.util.Map;
import java.util.function.Function;
import java.util.stream.Collectors;

/**
 * Represents the complete composition of a train for a specific segment of its journey.
 * Includes origin, destination, source of data, and the individual units (carriages/engines).
 */
public class TrainComposition {
    private final Vehicle vehicle;
    private final Station origin;
    private final Station destination;
    private final String compositionSource;
    private final List<? extends TrainCompositionUnit> units;

    /**
     * Constructs a new TrainComposition.
     *
     * @param vehicle           the vehicle associated with this composition
     * @param origin            the starting station for this composition segment
     * @param destination       the final station for this composition segment
     * @param compositionSource the source of the composition data (e.g., "Atlas")
     * @param units             the list of individual units forming the train
     */
    public TrainComposition(Vehicle vehicle, Station origin, Station destination, String compositionSource, List<? extends TrainCompositionUnit> units) {
        this.vehicle = vehicle;
        this.origin = origin;
        this.destination = destination;
        this.compositionSource = compositionSource;
        this.units = units;
    }

    /**
     * Gets the source system of this composition data.
     * @return the composition source
     */
    public String getCompositionSource() {
        return this.compositionSource;
    }

    /**
     * Gets the list of units that make up this train composition.
     * @return the list of units
     */
    public List<? extends TrainCompositionUnit> getUnits() {
        return this.units;
    }

    /**
     * Gets a specific unit from the composition by its index.
     *
     * @param i the index of the unit (0-based)
     * @return the requested unit
     */
    public TrainCompositionUnit getUnit(int i) {
        return this.units.get(i);
    }

    /**
     * Gets the number of units in this composition.
     * @return the composition length
     */
    public int getLength() {
        return this.units.size();
    }

    /**
     * Gets the vehicle associated with this composition.
     * @return the vehicle
     */
    public Vehicle getVehicle() {
        return this.vehicle;
    }

    /**
     * Gets the origin station for this composition segment.
     * @return the origin station
     */
    public Station getOrigin() {
        return this.origin;
    }

    /**
     * Gets the destination station for this composition segment.
     * @return the destination station
     */
    public Station getDestination() {
        return this.destination;
    }

    public String getPrimarySubType() {
        return getUnits().stream()
                .map(tcu -> tcu.getMaterialType().getSubType())
                // put all values in a value -> count map
                .collect(Collectors.groupingBy(Function.identity(), Collectors.counting()))
                .entrySet()
                .stream()
                .max(Map.Entry.comparingByValue()).orElse(null).getKey();
    }
}
