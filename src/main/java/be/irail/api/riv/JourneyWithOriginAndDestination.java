package be.irail.api.riv;

import be.irail.api.dto.Vehicle;

import java.time.LocalDate;
import java.util.List;

public record JourneyWithOriginAndDestination(
        LocalDate tripStartDate,
        String tripId,
        String vehicleType,
        int vehicleNumber,
        String originStopId,
        int originDepartureTime,
        String destinationStopId,
        int destinationArrivalTime,
        int numberOfStops,
        List<String> splitOrJoinStopIds
) {
    public String getJourneyType() {
        return vehicleType;
    }

    public int getJourneyNumber() {
        return vehicleNumber;
    }

    public String getOriginStopId() {
        return originStopId;
    }

    public int getOriginDepartureTimeOffset() {
        return originDepartureTime;
    }

    public String getDestinationStopId() {
        return destinationStopId;
    }

    public int getDestinationArrivalTimeOffset() {
        return destinationArrivalTime;
    }

    public String getTripId() {
        return tripId;
    }

    public List<String> getSplitOrJoinStopIds() {
        return splitOrJoinStopIds;
    }

    public Vehicle getVehicle() {
        return Vehicle.fromTypeAndNumber(vehicleType, vehicleNumber, tripStartDate);
    }

    public boolean hasSuffixInTripId() {
        // If the trip id ends on :0, :1 ,... it may be data shadowing another trip. These trips don't occur in the realtime data.
        return getTripId().charAt(getTripId().length() - 2) == ':';
    }
}
