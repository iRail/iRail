package be.irail.api.legacy;

import be.irail.api.dto.DepartureAndArrival;
import be.irail.api.dto.DepartureArrivalState;
import be.irail.api.dto.DepartureOrArrival;
import be.irail.api.dto.result.VehicleJourneySearchResult;

import java.time.ZoneId;
import java.util.List;

/**
 * Converts VehicleJourneySearchResult to V1 DataRoot format for legacy API compatibility.
 */
public class DatedVehicleJourneyV1Converter extends V1Converter {

    /**
     * Converts a vehicle journey search result to the V1 DataRoot format.
     *
     * @param vehicleJourney the vehicle journey search result
     * @return the DataRoot for V1 output
     */
    public static DataRoot convert(VehicleJourneySearchResult vehicleJourney) {
        DataRoot result = new DataRoot("vehicleinformation");
        List<DepartureAndArrival> visitedStops = vehicleJourney.getStops().stream()
                .filter(stop ->
                        (stop.hasDeparture() && stop.getDeparture().getStatus() != null)
                                || (stop.hasArrival() && stop.getArrival().getStatus() != null))
                .toList();
        var lastVisitedStop = visitedStops.isEmpty() ? vehicleJourney.getStops().getFirst() : visitedStops.getLast();
        result.vehicle = convertVehicle(vehicleJourney.getVehicle(), lastVisitedStop.getStation());
        result.stop = vehicleJourney.getStops().stream()
                .map(DatedVehicleJourneyV1Converter::convertStop)
                .toArray();
        return result;
    }

    private static V1Stop convertStop(DepartureAndArrival stop) {
        DepartureOrArrival departure = stop.getDeparture() != null ? stop.getDeparture() : stop.getArrival();
        DepartureOrArrival arrival = stop.getArrival() != null ? stop.getArrival() : stop.getDeparture();

        V1Stop result = new V1Stop();
        result.station = convertStation(stop.getStation());
        result.time = departure.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.platform = convertPlatform(departure.getPlatform());
        result.scheduledDepartureTime = departure.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.scheduledArrivalTime = arrival.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.delay = departure.getDelay();
        result.canceled = departure.isCancelled() ? "1" : "0";
        result.departureDelay = stop.getDeparture() != null ? stop.getDeparture().getDelay() : 0;
        result.departureCanceled = departure.isCancelled() ? "1" : "0";
        result.arrivalDelay = stop.getArrival() != null ? stop.getArrival().getDelay() : 0;
        result.arrivalCanceled = arrival.isCancelled() ? "1" : "0";
        result.left = (departure.getStatus() != null && departure.getStatus().hasLeft()) ? "1" : "0";
        result.arrived = (arrival.getStatus() == DepartureArrivalState.HALTING
                || arrival.getStatus() == DepartureArrivalState.REPORTED) ? "1" : "0";
        result.isExtraStop = (departure.isExtra() || arrival.isExtra()) ? "1" : "0";

        if (stop.getDeparture() != null) {
            result.occupancy = convertOccupancy(stop.getDeparture().getOccupancy());
            result.departureConnection = departure.getDepartureUri();
        }

        return result;
    }


    // Inner classes for V1 output structure
    public static class V1Stop {
        public V1Station station;
        public long time;
        public V1Platform platform;
        public long scheduledDepartureTime;
        public long scheduledArrivalTime;
        public int delay;
        public String canceled;
        public int departureDelay;
        public String departureCanceled;
        public int arrivalDelay;
        public String arrivalCanceled;
        public String left;
        public String arrived;
        public String isExtraStop;
        public V1Occupancy occupancy;
        public String departureConnection;
    }
}
