package be.irail.api.legacy;

import be.irail.api.dto.DepartureArrivalState;
import be.irail.api.dto.DepartureOrArrival;
import be.irail.api.dto.StationDto;
import be.irail.api.dto.TimeSelection;
import be.irail.api.dto.result.LiveboardSearchResult;
import be.irail.api.riv.requests.LiveboardRequest;

import java.time.ZoneId;

/**
 * Converts LiveboardSearchResult to V1 DataRoot format for legacy API compatibility.
 */
public class LiveboardV1Converter extends V1Converter {

    /**
     * Converts a liveboard search result to the V1 DataRoot format.
     *
     * @param request the original liveboard request
     * @param liveboard the liveboard search result
     * @return the DataRoot for V1 output
     */
    public static DataRoot convert(LiveboardRequest request, LiveboardSearchResult liveboard) {
        DataRoot result = new DataRoot("liveboard");
        result.station = convertStation(liveboard.getStation());
        
        if (request.timeSelection() == TimeSelection.DEPARTURE) {
            result.departure = liveboard.getStops().stream()
                    .map(LiveboardV1Converter::convertDeparture)
                    .toArray();
        } else {
            result.arrival = liveboard.getStops().stream()
                    .map(LiveboardV1Converter::convertArrival)
                    .toArray();
        }
        return result;
    }

    private static V1Departure convertDeparture(DepartureOrArrival departure) {
        V1Departure result = new V1Departure();
        result.station = convertDirectionStation(departure);
        result.time = departure.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.delay = departure.getDelay();
        result.canceled = departure.isCancelled() ? "1" : "0";
        result.left = departure.getStatus() == DepartureArrivalState.REPORTED ? "1" : "0";
        result.isExtra = departure.isExtra() ? "1" : "0";
        result.vehicle = convertVehicle(departure.getVehicle(), null);
        result.platform = convertPlatform(departure.getPlatform());
        result.occupancy = convertOccupancy(departure.getOccupancy());
        result.departureConnection = departure.getDepartureUri();
        return result;
    }

    private static V1Arrival convertArrival(DepartureOrArrival arrival) {
        V1Arrival result = new V1Arrival();
        result.station = convertDirectionStation(arrival);
        result.time = arrival.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.delay = arrival.getDelay();
        result.canceled = arrival.isCancelled() ? "1" : "0";
        result.arrived = arrival.getStatus() == DepartureArrivalState.REPORTED ? "1" : "0";
        result.isExtra = arrival.isExtra() ? "1" : "0";
        result.vehicle = convertVehicle(arrival.getVehicle(), null);
        result.platform = convertPlatform(arrival.getPlatform());
        result.departureConnection = arrival.getDepartureUri();
        return result;
    }

    /**
     * Converts the station a vehicle is heading to (or coming from), when it is known.
     *
     * @param departureOrArrival the departure or arrival to read the direction from
     * @return the converted direction station, or null if the vehicle, its direction or the
     *         direction's station is unknown
     */
    private static V1Station convertDirectionStation(DepartureOrArrival departureOrArrival) {
        if (departureOrArrival.getVehicle() == null || departureOrArrival.getVehicle().getDirection() == null) {
            return null;
        }
        StationDto station = departureOrArrival.getVehicle().getDirection().getStation();
        return station != null ? convertStation(station) : null;
    }

    // Inner classes for V1 output structure

    public static class V1Departure {
        public V1Station station;
        public long time;
        public int delay;
        public String canceled;
        public String left;
        public String isExtra;
        public V1Vehicle vehicle;
        public V1Platform platform;
        public V1Occupancy occupancy;
        public String departureConnection;
    }

    public static class V1Arrival {
        public V1Station station;
        public long time;
        public int delay;
        public String canceled;
        public String arrived;
        public String isExtra;
        public V1Vehicle vehicle;
        public V1Platform platform;
        public String departureConnection;
    }
}
