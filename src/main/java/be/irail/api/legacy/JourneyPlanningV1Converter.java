package be.irail.api.legacy;

import be.irail.api.dto.*;
import be.irail.api.dto.result.JourneyPlanningSearchResult;

import java.time.ZoneId;
import java.util.ArrayList;
import java.util.List;

/**
 * Converts JourneyPlanningSearchResult to V1 DataRoot format for legacy API compatibility.
 */
public class JourneyPlanningV1Converter extends V1Converter {

    /**
     * Converts a journey planning search result to the V1 DataRoot format.
     *
     * @param journeyPlanning the journey planning search result
     * @return the DataRoot for V1 output
     */
    public static DataRoot convert(JourneyPlanningSearchResult journeyPlanning) {
        DataRoot result = new DataRoot("connections");
        result.connection = journeyPlanning.getJourneys().stream()
                .map(JourneyPlanningV1Converter::convertJourneyPlan)
                .toArray();
        return result;
    }

    private static V1Connection convertJourneyPlan(Journey journey) {
        V1Connection result = new V1Connection();
        result.departure = convertDeparture(journey.getDeparture(), journey.getLegs().getFirst());
        result.arrival = convertArrival(journey.getArrival(), journey.getLegs().getLast());
        if (journey.getLegs().size() > 1) {
            result.via = convertVias(journey.getLegs());
        }
        result.duration = journey.getDurationSeconds();
        result.remark = new Object[0];

        List<Message> allAlertsInJourney = new ArrayList<>();
        for (JourneyLeg leg : journey.getLegs()) {
            allAlertsInJourney.addAll(leg.getAlerts());
        }
        result.alert = convertAlerts(allAlertsInJourney);
        return result;
    }

    private static V1ConnectionDeparture convertDeparture(DepartureOrArrival departure, JourneyLeg departureLeg) {
        V1ConnectionDeparture result = new V1ConnectionDeparture();
        result.delay = departure.getDelay();
        result.station = convertStation(departure.getStation());
        result.time = departure.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.vehicle = departureLeg.getLegType() == JourneyLegType.JOURNEY
                ? convertVehicle(departure.getVehicle(), null)
                : convertWalk();
        result.platform = convertPlatform(departure.getPlatform());
        result.canceled = departure.isCancelled() ? "1" : "0";
        result.stop = departureLeg.getIntermediateStops().stream()
                .map(JourneyPlanningV1Converter::convertIntermediateStop)
                .toArray(V1IntermediateStop[]::new);
        result.departureConnection = departure.getDepartureUri();
        result.direction = convertDirection(departure);
        result.left = (departure.getStatus() != null && departure.getStatus().hasLeft()) ? "1" : "0";
        result.walking = departureLeg.getLegType() == JourneyLegType.WALKING ? "1" : "0";
        result.occupancy = convertOccupancy(departure.getOccupancy());
        return result;
    }

    private static V1ConnectionArrival convertArrival(DepartureOrArrival arrival, JourneyLeg arrivalLeg) {
        V1ConnectionArrival result = new V1ConnectionArrival();
        result.delay = arrival.getDelay();
        result.station = convertStation(arrival.getStation());
        result.time = arrival.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.vehicle = arrivalLeg.getLegType() == JourneyLegType.JOURNEY
                ? convertVehicle(arrival.getVehicle(), null)
                : convertWalk();
        result.platform = convertPlatform(arrival.getPlatform());
        result.canceled = arrival.isCancelled() ? "1" : "0";
        result.direction = convertDirection(arrival);
        result.arrived = (arrival.getStatus() != null && arrival.getStatus().hasArrived()) ? "1" : "0";
        result.walking = arrivalLeg.getLegType() == JourneyLegType.WALKING ? "1" : "0";
        result.departureConnection = arrival.getDepartureUri();
        return result;
    }

    private static V1Via[] convertVias(List<JourneyLeg> legs) {
        List<V1Via> result = new ArrayList<>();
        for (int i = 0; i < legs.size() - 1; i++) {
            JourneyLeg arrivingLeg = legs.get(i);
            JourneyLeg departingLeg = legs.get(i + 1);
            V1Via via = new V1Via();
            via.arrival = convertArrival(arrivingLeg.getArrival(), arrivingLeg);
            via.departure = convertDeparture(departingLeg.getDeparture(), departingLeg);
            via.timebetween = departingLeg.getDeparture().getRealtimeDateTime().atZone(ZoneId.systemDefault()).toEpochSecond()
                    - arrivingLeg.getArrival().getRealtimeDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
            via.station = convertStation(arrivingLeg.getArrival().getStation());
            via.vehicle = departingLeg.getLegType() == JourneyLegType.JOURNEY
                    ? convertVehicle(departingLeg.getVehicle(), null)
                    : convertWalk();
            result.add(via);
        }
        return result.toArray(new V1Via[0]);
    }

    private static V1IntermediateStop convertIntermediateStop(DepartureAndArrival stop) {
        DepartureOrArrival departure = stop.getDeparture();
        DepartureOrArrival arrival = stop.getArrival() != null ? stop.getArrival() : departure;

        V1IntermediateStop result = new V1IntermediateStop();
        result.station = convertStation(stop.getStation());
        result.scheduledArrivalTime = arrival.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.arrivalCanceled = arrival.isCancelled() ? "1" : "0";
        result.scheduledDepartureTime = departure.getScheduledDateTime().atZone(ZoneId.systemDefault()).toEpochSecond();
        result.arrivalDelay = arrival.getDelay();
        result.departureDelay = departure.getDelay();
        result.departureCanceled = departure.isCancelled() ? "1" : "0";
        result.left = (departure.getStatus() != null && departure.getStatus().hasLeft()) ? "1" : "0";
        result.arrived = (arrival.getStatus() != null && arrival.getStatus().hasArrived()) ? "1" : "0";
        result.isExtraStop = (departure.isExtra() || arrival.isExtra()) ? "1" : "0";
        result.platform = convertPlatform(new PlatformInfo(null, "?", false));
        return result;
    }

    private static V1Alert[] convertAlerts(List<Message> alerts) {
        return alerts.stream().map(message -> {
            V1Alert alert = new V1Alert();
            alert.header = message.getHeader();
            alert.description = message.getStrippedMessage();
            alert.lead = message.getLeadText();
            alert.startTime = message.getValidFrom() != null ? message.getValidFrom().toEpochSecond() : 0;
            alert.endTime = message.getValidUpTo() != null ? message.getValidUpTo().toEpochSecond() : 0;
            return alert;
        }).toArray(V1Alert[]::new);
    }

    private static V1Direction convertDirection(DepartureOrArrival departureOrArrival) {
        V1Direction result = new V1Direction();
        if (departureOrArrival.getVehicle() != null && departureOrArrival.getVehicle().getDirection() != null) {
            StationDto station = departureOrArrival.getVehicle().getDirection().getStation();
            result.name = station != null ? station.getLocalizedStationName() : departureOrArrival.getVehicle().getDirection().getName();
        } else {
            result.name = "Walk";
        }
        return result;
    }

    private static V1Vehicle convertWalk() {
        V1Vehicle result = new V1Vehicle();
        result.name = "BE.NMBS.WALK";
        result.shortname = "WALK";
        result.number = "";
        result.type = "";
        result.locationX = "0";
        result.locationY = "0";
        result.atId = "";
        return result;
    }

    // Inner classes for V1 output structure


    public static class V1Connection {
        public V1ConnectionDeparture departure;
        public V1ConnectionArrival arrival;
        public V1Via[] via;
        public long duration;
        public Object[] remark;
        public V1Alert[] alert;
    }

    public static class V1ConnectionDeparture {
        public int delay;
        public V1Station station;
        public long time;
        public V1Vehicle vehicle;
        public V1Platform platform;
        public String canceled;
        public V1IntermediateStop[] stop;
        public String departureConnection;
        public V1Direction direction;
        public String left;
        public String walking;
        public V1Occupancy occupancy;
    }

    public static class V1ConnectionArrival {
        public int delay;
        public V1Station station;
        public long time;
        public V1Vehicle vehicle;
        public V1Platform platform;
        public String canceled;
        public V1Direction direction;
        public String arrived;
        public String walking;
        public String departureConnection;
    }

    public static class V1Via {
        public V1ConnectionArrival arrival;
        public V1ConnectionDeparture departure;
        public long timebetween;
        public V1Station station;
        public V1Vehicle vehicle;
    }

    public static class V1IntermediateStop {
        public V1Station station;
        public long scheduledArrivalTime;
        public String arrivalCanceled;
        public long scheduledDepartureTime;
        public int arrivalDelay;
        public int departureDelay;
        public String departureCanceled;
        public String left;
        public String arrived;
        public String isExtraStop;
        public V1Platform platform;
    }

}
