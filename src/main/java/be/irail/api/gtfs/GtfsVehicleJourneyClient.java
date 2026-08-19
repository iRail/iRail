package be.irail.api.gtfs;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.VehicleJourneySearchResult;
import be.irail.api.exception.notfound.JourneyNotFoundException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.reader.models.*;
import be.irail.api.riv.JourneyWithOriginAndDestination;
import be.irail.api.riv.requests.VehicleJourneyRequest;
import be.irail.api.util.VehicleIdTools;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.*;

/**
 * Client for fetching and parsing NMBS Vehicle Journey data from their GTFS feed.
 */
@Service
public class GtfsVehicleJourneyClient {
    private static final Logger log = LogManager.getLogger(GtfsVehicleJourneyClient.class);
    private final StationsDao stationsDao;
    private final OccupancyDao occupancyDao;

    public GtfsVehicleJourneyClient(
            StationsDao stationsDao,
            OccupancyDao occupancyDao
    ) {
        this.stationsDao = stationsDao;
        this.occupancyDao = occupancyDao;
    }

    public VehicleJourneySearchResult getDatedVehicleJourney(VehicleJourneyRequest request) {
        int vehicleNumber = VehicleIdTools.extractTrainNumber(request.vehicleId());
        Optional<JourneyWithOriginAndDestination> vehicleWithOriginAndDestination = GtfsInMemoryDao.getInstance().getVehicleWithOriginAndDestination(vehicleNumber, request.dateTime());
        if (vehicleWithOriginAndDestination.isEmpty()) {
            throw new JourneyNotFoundException(request.vehicleId(), request.dateTime(), "Vehicle not found in GTFS data");
        }
        GtfsInMemoryDao staticDao = GtfsInMemoryDao.getInstance();
        GtfsRtInMemoryDao rtDao = GtfsRtInMemoryDao.getInstance();
        JourneyWithOriginAndDestination journey = vehicleWithOriginAndDestination.get();
        String tripId = journey.getTripId();
        Trip trip = staticDao.getTrip(tripId);
        Route route = staticDao.getRoute(trip.routeId());
        LocalDate startDate = journey.tripStartDate();

        Vehicle vehicle = journey.getVehicle();
        List<StopTime> stopTimesForTrip = GtfsInMemoryDao.getInstance().getStopTimesForTrip(tripId);
        List<DepartureAndArrival> stops = new ArrayList<>();

        boolean isCanceled = rtDao.isCanceled(tripId, startDate);
        Map<String, GtfsRtUpdate> updatesForTripByStopId = rtDao.getUpdatesByTripId(tripId);
        for (StopTime stopTime : stopTimesForTrip) {
            if (!stopTime.hasScheduledPassengerExchange()) {
                // This is just a waypoint
                // TODO: Add support for waypoints in the model, this information can lead to better route visualisations
                continue;
            }

            Stop platform = staticDao.getStop(stopTime, startDate);
            String parentStationId = platform.parentStation();
            String hafasStationId = Stop.getHafasId(parentStationId);
            if (hafasStationId == null) {
                // Name-based international parents cannot be mapped directly,
                // but their platform identifier generally contains the UIC ID.
                hafasStationId = platform.getHafasId();
            }
            if (parentStationId == null) {
                // E.g border stops which are passed
                log.warn("Stop " + stopTime.stopId() + " (" + platform.name() + ") for vehicle " + vehicle.getName() + " has no parent station");
            }
            Station station = hafasStationId == null ? null : stationsDao.getStationFromId(hafasStationId);
            Stop gtfsStation = parentStationId == null ? platform : staticDao.getStop(parentStationId);
            StationDto stationDto = station != null
                    ? station.toDto(request.language())
                    : gtfsStationDto(gtfsStation != null ? gtfsStation : platform, hafasStationId, request.language());

            DepartureOrArrival arrival = new DepartureOrArrival();
            arrival.setStation(stationDto);
            arrival.setPlatform(new PlatformInfo(parentStationId, platform.platformCode(), false));
            arrival.setScheduledDateTime(stopTime.getArrivalTime(startDate));
            arrival.setIsCancelled(isCanceled);
            arrival.setVehicle(vehicle);

            DepartureAndArrival stop = new DepartureAndArrival();
            DepartureOrArrival departure = new DepartureOrArrival();
            departure.setStation(stationDto);
            departure.setPlatform(new PlatformInfo(parentStationId, platform.platformCode(), false));
            departure.setScheduledDateTime(stopTime.getDepartureTime(startDate));
            departure.setIsCancelled(isCanceled);
            departure.setVehicle(vehicle);
            departure.setOccupancy(occupancyDao.getOccupancy(departure));

            GtfsRtUpdate update = updatesForTripByStopId.getOrDefault(platform.id(), null);
            LocalDateTime now = LocalDateTime.now();
            if (update != null
                    && GtfsRtInMemoryDao.isOverlayUsable(update.timestamp(), departure.getScheduledDateTime(), now)) {
                arrival.setDelay(update.arrivalDelay());
                departure.setDelay(update.departureDelay());

                if (arrival.getScheduledDateTime().plusSeconds(update.arrivalDelay()).isBefore(now)) {
                    arrival.setStatus(DepartureArrivalState.REPORTED);
                }
                if (departure.getScheduledDateTime().plusSeconds(update.departureDelay()).isBefore(now)) {
                    departure.setStatus(DepartureArrivalState.REPORTED);
                }

                arrival.setIsCancelled(arrival.isCancelled() || update.cancelled());
                departure.setIsCancelled(departure.isCancelled() || update.cancelled());
                String newPlatform = staticDao.getStop(update.stopId()).platformCode();
                if (!update.stopId().equals(platform.id())) {
                    arrival.setPlatform(new PlatformInfo(update.parentStopId(), newPlatform, true));
                    departure.setPlatform(new PlatformInfo(update.parentStopId(), newPlatform, true));
                }
            }

            if (stopTime.pickupType() == PickupDropoffType.SCHEDULED) {
                stop.setDeparture(departure);
            }
            if (stopTime.dropOffType() == PickupDropoffType.SCHEDULED) {
                stop.setArrival(arrival);
            }
            stops.add(stop);
        }

        vehicle.setDirection(new VehicleDirection(route.longName(), stops.getLast().getStation()));
        return new VehicleJourneySearchResult(vehicle, stops, Collections.emptyList());
    }

    private StationDto gtfsStationDto(Stop stop, String hafasId, Language language) {
        String id = hafasId == null ? stop.id() : "00" + hafasId;
        String uri = hafasId == null ? null : "http://irail.be/stations/NMBS/" + id;
        return new StationDto(id, uri, stop.name(), stop.getName(language), stop.lon(), stop.lat());
    }

}
