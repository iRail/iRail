package be.irail.api.dto;

import be.irail.api.db.CompositionStatistics;
import be.irail.api.dto.result.VehicleCompositionSearchResult;
import be.irail.api.dto.vehiclecomposition.TrainComposition;
import java.time.Duration;
import java.util.ArrayList;
import java.util.List;

/**
 * Represents a single leg of a journey (e.g., a train ride between two stations).
 * Contains departure and arrival information, intermediate stops, alerts, and composition data.
 */
public class JourneyLeg {
    private Vehicle vehicle;
    private DepartureOrArrival arrival;
    private DepartureOrArrival departure;
    private List<DepartureAndArrival> intermediateStops = new ArrayList<>();
    private JourneyLegType legType;
    private List<Message> alerts = new ArrayList<>();
    private boolean reachable;
    private List<CompositionStatistics> historicCompositionBySegment = new ArrayList<>();
    private List<TrainComposition> composition = new ArrayList<>();

    /**
     * Constructs a new JourneyLeg with departure and arrival information.
     *
     * @param departure the departure stop
     * @param arrival the arrival stop
     */
    public JourneyLeg(DepartureOrArrival departure, DepartureOrArrival arrival) {
        this.departure = departure;
        this.arrival = arrival;
    }

    /**
     * Gets the departure information for this leg.
     * @return the departure stop
     */
    public DepartureOrArrival getDeparture() {
        return this.departure;
    }

    /**
     * Sets the departure information for this leg.
     * @param departure the departure stop to set
     */
    public void setDeparture(DepartureOrArrival departure) {
        this.departure = departure;
    }

    /**
     * Gets the arrival information for this leg.
     * @return the arrival stop
     */
    public DepartureOrArrival getArrival() {
        return this.arrival;
    }

    /**
     * Sets the arrival information for this leg.
     * @param arrival the arrival stop to set
     */
    public void setArrival(DepartureOrArrival arrival) {
        this.arrival = arrival;
    }

    /**
     * Calculates the duration of this leg based on real-time data.
     * @return the duration, or null if schedule data is missing
     */
    public Duration getDuration() {
        if (this.departure.getRealtimeDateTime() == null || this.arrival.getRealtimeDateTime() == null) {
            return null;
        }
        return Duration.between(this.departure.getRealtimeDateTime(), this.arrival.getRealtimeDateTime());
    }

    /**
     * Gets the duration of this leg in seconds.
     * @return the duration in seconds
     */
    public long getDurationSeconds() {
        Duration duration = getDuration();
        return duration != null ? duration.getSeconds() : 0;
    }

    /**
     * Sets the list of intermediate stops for this leg.
     * @param intermediateStops the list of intermediate stops
     */
    public void setIntermediateStops(List<DepartureAndArrival> intermediateStops) {
        this.intermediateStops = intermediateStops;
    }

    /**
     * Gets the list of intermediate stops for this leg.
     * @return the list of intermediate stops
     */
    public List<DepartureAndArrival> getIntermediateStops() {
        return this.intermediateStops;
    }

    /**
     * Gets the vehicle used in this leg.
     * @return the vehicle, or null if not a vehicle-based leg (e.g., walking)
     */
    public Vehicle getVehicle() {
        return this.vehicle;
    }

    /**
     * Sets the vehicle for this leg and propagates it to departure and arrival stops.
     * @param vehicle the vehicle to set
     */
    public void setVehicle(Vehicle vehicle) {
        this.vehicle = vehicle;
        if (this.departure != null) this.departure.setVehicle(vehicle);
        if (this.arrival != null) this.arrival.setVehicle(vehicle);
    }

    /**
     * Gets the type of this leg (WALK, JNY, etc.).
     * @return the leg type
     */
    public JourneyLegType getLegType() {
        return this.legType;
    }

    /**
     * Sets the type of this leg.
     * @param legType the leg type to set
     */
    public void setLegType(JourneyLegType legType) {
        this.legType = legType;
    }

    /**
     * Gets service alerts specific to this leg.
     * @return a list of alert messages
     */
    public List<Message> getAlerts() {
        return this.alerts;
    }

    /**
     * Sets service alerts for this leg.
     * @param alerts the list of alerts to set
     */
    public void setAlerts(List<Message> alerts) {
        this.alerts = alerts;
    }

    /**
     * Checks if this leg is reachable considering previous leg delays and transfer times.
     *
     * @return true if the leg can be reached before departure
     */
    public boolean isReachable() {
        return this.reachable;
    }

    /**
     * Sets whether this leg is reachable.
     * @param reachable true if reachable
     */
    public void setReachable(boolean reachable) {
        this.reachable = reachable;
    }

    /**
     * Sets historic composition statistics for this leg's vehicle.
     * @param historicCompositionData the statistics to set
     */
    public void setHistoricCompositionStatistics(List<CompositionStatistics> historicCompositionData) {
        this.historicCompositionBySegment = historicCompositionData;
    }

    /**
     * Sets the real-time composition information for this leg.
     * @param compositionResult the composition result to extract from
     */
    public void setComposition(VehicleCompositionSearchResult compositionResult) {
        this.composition = compositionResult.getSegments();
    }

    /**
     * Gets historic composition statistics by segment.
     * @return a list of composition statistics
     */
    public List<CompositionStatistics> getCompositionStatsBySegment() {
        return this.historicCompositionBySegment;
    }

    /**
     * Gets the real-time train composition information.
     * @return a list of train composition segments
     */
    public List<TrainComposition> getComposition() {
        return this.composition;
    }
}
