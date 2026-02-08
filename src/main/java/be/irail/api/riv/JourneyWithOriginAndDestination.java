package be.irail.api.riv;

import java.util.List;

public record JourneyWithOriginAndDestination(
    String tripId,
    String vehicleType,
    int vehicleNumber,
    String originStopId,
    int originDepartureTime,
    String destinationStopId,
    int destinationArrivalTime,
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
}
