package be.irail.api.dto.result;

import be.irail.api.dto.DepartureOrArrival;
import be.irail.api.dto.StationDto;
import java.util.List;

/**
 * Result of a liveboard search for a specific station.
 * Contains the station information and a list of upcoming departures or arrivals.
 */
public class LiveboardSearchResult {
    private final StationDto station;
    private final List<DepartureOrArrival> stops;

    /**
     * Constructs a new LiveboardSearchResult.
     *
     * @param station the station for which the liveboard was requested
     * @param stops the list of vehicle stops found
     */
    public LiveboardSearchResult(StationDto station, List<DepartureOrArrival> stops) {
        this.station = station;
        this.stops = stops;
    }

    /**
     * Gets the station associated with this liveboard.
     * @return the station
     */
    public StationDto getStation() {
        return station;
    }

    /**
     * Gets the list of vehicle stops (departures or arrivals) at the station.
     * @return the list of stops
     */
    public List<DepartureOrArrival> getStops() {
        return stops;
    }
}
