package be.irail.api.gtfs.dao;

import be.irail.api.dto.Vehicle;
import be.irail.api.exception.notfound.JourneyNotFoundException;
import be.irail.api.gtfs.dao.models.Call;
import be.irail.api.gtfs.dao.models.Journey;
import be.irail.api.gtfs.reader.GtfsReader;
import be.irail.api.gtfs.reader.models.*;
import com.google.common.collect.ArrayListMultimap;
import com.google.common.collect.HashMultimap;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.*;
import java.util.stream.Collectors;

/**
 * In-memory DAO for GTFS data, stored for performant lookups.
 */
public class GtfsInMemoryDao {
    private static final Logger log = LogManager.getLogger(GtfsInMemoryDao.class);
    private static volatile GtfsInMemoryDao instance = null;

    private final Map<String, Agency> agencies;
    private final HashMultimap<Integer, LocalDate> calendarDatesByServiceId;
    private final HashMultimap<LocalDate, TripIdAndStartDate> tripIdsByDate;
    private final HashMultimap<LocalDate, TripIdAndStartDate> tripsStartedAtPreviousDayByDate;
    private final Map<String, Route> routes;
    private final Map<String, Stop> stops;
    private final ArrayListMultimap<String, StopTime> stopTimesByTripId;
    private final ArrayListMultimap<String, StopTime> stopTimesByStopId;
    private final ArrayListMultimap<Integer, Trip> tripsByShortName;
    private final HashMap<String, Trip> tripsById;

    public GtfsInMemoryDao(GtfsReader.GtfsData data) {
        log.warn("Creating new GtfsInMemoryDao, this can be a memory intensive process");
        log.info("Current heap memory usage {}", Runtime.getRuntime().totalMemory() / 1024 / 1024);
        this.agencies = new HashMap<>();
        data.agencies().forEach(a -> agencies.put(a.id(), a));


        this.calendarDatesByServiceId = HashMultimap.create();
        data.calendarDates().forEach(cd -> {
            calendarDatesByServiceId.put(cd.serviceId(), cd.date());
        });

        this.routes = new HashMap<>();
        data.routes().forEach(r -> routes.put(r.id(), r));

        this.stops = new HashMap<>();
        data.stops().forEach(s -> stops.put(s.id(), s));

        this.tripsByShortName = ArrayListMultimap.create();
        this.tripIdsByDate = HashMultimap.create();
        this.tripsById = new HashMap<>();
        data.trips().forEach(t -> {
            tripsByShortName.put(t.shortName(), t);
            calendarDatesByServiceId.get(t.serviceId()).forEach(date -> tripIdsByDate.put(date, new TripIdAndStartDate(t.id(), date)));
            tripsById.put(t.id(), t);
        });

        this.stopTimesByTripId = ArrayListMultimap.create();
        this.stopTimesByStopId = ArrayListMultimap.create();
        this.tripsStartedAtPreviousDayByDate = HashMultimap.create();
        data.stopTimes().forEach(stopTime -> {
            stopTimesByTripId.put(stopTime.tripId(), stopTime);
            Stop platformStop = stops.get(stopTime.stopId());
            if (platformStop.parentStation() == null) {
                // This happens for example at border stops, or stops which are not in use yet
                // log.debug("Stop " + stopTime.stopId() + " (" + platformStop.name() + ") has no parent station");
                return;
            }
            // Remove the "S" prefix for station type stops
            String parentStopId = platformStop.parentStation().replaceAll("[^0-9]", "");
            stopTimesByStopId.put(parentStopId, stopTime);
            if (stopTime.arrivalOffsetSeconds() > 86400) {
                calendarDatesByServiceId.get(tripsById.get(stopTime.tripId()).serviceId()).forEach(date -> {
                    tripsStartedAtPreviousDayByDate.put(date.plusDays(1), new TripIdAndStartDate(stopTime.tripId(), date));
                });
            }
        });

        // Sort overrides by sequence
        stopTimesByTripId.keySet().forEach(key -> stopTimesByTripId.get(key).sort(Comparator.comparingInt(StopTime::stopSequence)));
        log.info("Created new GtfsInMemoryDao");
        log.info("Current heap memory usage {}", Runtime.getRuntime().totalMemory() / 1024 / 1024);
    }

    public static GtfsInMemoryDao getInstance() {
        return instance;
    }

    public static void setInstance(GtfsInMemoryDao newInstance) {
        log.info("Updating GTFSInMemoryDao");
        instance = newInstance;
    }

    public Stop getStop(String stopId) {
        return stops.get(stopId);
    }

    public List<Trip> getTripsByJourneyNumber(Integer shortName) {
        return tripsByShortName.get(shortName);
    }

    public List<StopTime> getStopTimesForTrip(String tripId) {
        return stopTimesByTripId.get(tripId);
    }

    public Set<LocalDate> getCalendarDates(Integer serviceId) {
        return calendarDatesByServiceId.get(serviceId);
    }

    public Route getRoute(String routeId) {
        return routes.get(routeId);
    }

    /**
     * Returns a Journey by its journey number (trip_short_name).
     */
    public Journey getJourneyByNumber(int journeyNumber, LocalDate date) {
        List<Trip> matchingTrips = tripsByShortName.get(journeyNumber);
        if (matchingTrips.isEmpty()) {
            return null;
        }

        // For now, just take the first one as this method seems to be used loosely
        Trip trip = matchingTrips.getFirst();
        TripIdAndStartDate tripToLookUp = new TripIdAndStartDate(trip.id(), date);
        Set<TripIdAndStartDate> activeTripIds = tripIdsByDate.get(date);
        List<Trip> matchingTripsOnDate = matchingTrips.stream().filter(t -> activeTripIds.contains(tripToLookUp)).toList();
        if (matchingTripsOnDate.isEmpty()) {
            return null;
        }
        // Let the GTFS data fixing begin
        if (matchingTripsOnDate.size() > 1) {
            matchingTripsOnDate = matchingTrips.stream().filter(t -> t.id().endsWith(":2")).toList();
            if (matchingTripsOnDate.size() == 1) {
                log.warn("Found duplicate trip for journey number " + journeyNumber + " on " + date + ", ignored :2 prefix variant");
            }
        }
        if (matchingTripsOnDate.size() > 1) {
            throw new IllegalStateException(String.format("Multiple trips found for journey number " + journeyNumber + " on " + date + ": "
                    + matchingTripsOnDate.stream().map(Trip::id).collect(Collectors.joining(","))));
        }

        Route route = routes.get(trip.routeId());
        List<StopTime> overrides = stopTimesByTripId.containsKey(trip.id()) ? stopTimesByTripId.get(trip.id()) : Collections.emptyList();

        List<Call> calls = overrides.stream().map(sto -> {
            Stop platform = stops.get(sto.stopId());
            Stop station = null;
            if (platform != null && platform.parentStation() != null) {
                station = stops.get(platform.parentStation());
            }
            return new Call(sto, platform, station);
        }).toList();

        return new Journey(route, trip, calls);
    }

    public Vehicle getVehicle(int journeyNumber, LocalDate date) {
        Journey journeyByNumber = getJourneyByNumber(journeyNumber, date);
        if (journeyByNumber == null) {
            throw new JourneyNotFoundException(journeyNumber, date);
        }
        return Vehicle.fromTypeAndNumber(journeyByNumber.route().shortName(), journeyByNumber.trip().shortName(), date);
    }

    public Trip getTrip(String tripId) {
        return tripsById.get(tripId);
    }

    public Stop getStop(StopTime stopTime, LocalDate startDate) {
        if (stopTime.stopIdOverridesByServiceId().isEmpty()) {
            return stops.get(stopTime.stopId());
        }

        List<Integer> overridesOnDate = stopTime.stopIdOverridesByServiceId().keySet().stream().filter(serviceId -> getCalendarDates(serviceId).contains(startDate)).toList();
        if (overridesOnDate.isEmpty()) {
            return stops.get(stopTime.stopId());
        }
        if (overridesOnDate.size() > 1) {
            log.warn("Multiple stop IDs on stop_time for trip {}, stop {}, total count {} on date {}", stopTime.tripId(), stopTime.stopId(), overridesOnDate.size(), startDate);
        }
        return stops.get(stopTime.stopIdOverridesByServiceId().get(overridesOnDate.getFirst()));
    }

    public List<CallAtStop> getCallsAtStop(String stopId, LocalDateTime startTime, LocalDateTime endTime, boolean timeFilterDepartures) {
        List<StopTime> stopTimes = stopTimesByStopId.get(stopId);
        Set<TripIdAndStartDate> activeTripIds = tripIdsByDate.get(startTime.toLocalDate());
        for (LocalDate date = startTime.toLocalDate().plusDays(1); !date.isAfter(endTime.toLocalDate()); date = date.plusDays(1)) {
            activeTripIds.addAll(tripIdsByDate.get(date));
        }
        activeTripIds.addAll(tripsStartedAtPreviousDayByDate.get(startTime.toLocalDate()));
        List<CallAtStop> activeStopTimes = new ArrayList<>();
        for (LocalDate date = startTime.toLocalDate().minusDays(1); !date.isAfter(endTime.toLocalDate()); date = date.plusDays(1)) {
            for (StopTime stopTime : stopTimes) {
                if (!stopTime.hasScheduledPassengerExchange()) {
                    continue;
                }
                if (activeTripIds.contains(new TripIdAndStartDate(stopTime.tripId(), date))) {
                    LocalDateTime departureTime = stopTime.getDepartureTime(date);
                    LocalDateTime arrivalTime = stopTime.getArrivalTime(date);
                    if ((timeFilterDepartures && !endTime.isBefore(departureTime) && !startTime.isAfter(departureTime))
                            || (!timeFilterDepartures && !endTime.isBefore(arrivalTime) && !startTime.isAfter(arrivalTime))) {
                        Trip trip = tripsById.get(stopTime.tripId());
                        Route route = routes.get(trip.routeId());
                        Stop originStop = stops.get(stopTimesByTripId.get(stopTime.tripId()).getFirst().stopId());
                        Stop originParentStop = stops.get(originStop.parentStation());
                        Stop destinationStop = stops.get(stopTimesByTripId.get(stopTime.tripId()).getLast().stopId());
                        Stop destinationParentStop = stops.get(destinationStop.parentStation());
                        Stop platform = getStop(stopTime, date);
                        activeStopTimes.add(new CallAtStop(route, trip, platform, date, stopTime,
                                originParentStop == null ? originStop : originParentStop,
                                destinationParentStop == null ? destinationStop : destinationParentStop));
                    }
                }
            }
        }
        return activeStopTimes;
    }

    public record CallAtStop(Route route, Trip trip, Stop platform, LocalDate startDate, StopTime stopTime,
                             Stop originParentStop, Stop destinationParentStop) {

    }

    record TripIdAndStartDate(String tripId, LocalDate startDate) {

    }

}
