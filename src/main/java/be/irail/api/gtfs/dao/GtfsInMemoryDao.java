package be.irail.api.gtfs.dao;

import be.irail.api.dto.Vehicle;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.exception.notfound.JourneyNotFoundException;
import be.irail.api.gtfs.reader.GtfsReader;
import be.irail.api.gtfs.reader.models.*;
import be.irail.api.riv.JourneyWithOriginAndDestination;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.collect.ArrayListMultimap;
import com.google.common.collect.HashMultimap;
import com.google.common.util.concurrent.UncheckedExecutionException;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.*;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

/**
 * In-memory DAO for GTFS data, stored for performant lookups.
 */
public class GtfsInMemoryDao {
    private static final Logger log = LogManager.getLogger(GtfsInMemoryDao.class);
    private static final int SECONDS_IN_DAY = 86400;
    private static final int SERVICE_DAY_END_HOUR = 4;
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
    private final Cache<JourneyNumberAndDate, Optional<JourneyWithOriginAndDestination>> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(4, TimeUnit.HOURS)
            .build();

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

    private static boolean shouldConsiderTrainsFromPreviousServiceDaysForQuery(LocalDateTime date) {
        return date.getHour() < SERVICE_DAY_END_HOUR && date.toLocalDate().equals(LocalDate.now());
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

    public Vehicle getVehicle(int journeyNumber, LocalDate date) {
        Optional<JourneyWithOriginAndDestination> journeyByNumber = getVehicleWithOriginAndDestination(journeyNumber, LocalDateTime.of(date, LocalTime.now()));
        if (journeyByNumber.isEmpty()) {
            throw new JourneyNotFoundException(journeyNumber, date);
        }
        JourneyWithOriginAndDestination journey = journeyByNumber.get();
        return Vehicle.fromTypeAndNumber(journey.getJourneyType(), journey.getJourneyNumber(), journey.tripStartDate());
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

    public Optional<LocalDate> getStartDate(int journeyNumber, LocalDateTime plannedDateTime) throws JourneyNotFoundException {
        Optional<JourneyWithOriginAndDestination> journey = getVehicleWithOriginAndDestination(journeyNumber, plannedDateTime);
        return journey.map(JourneyWithOriginAndDestination::tripStartDate);
    }

    public Optional<JourneyWithOriginAndDestination> getVehicleWithOriginAndDestination(int journeyNumber, LocalDateTime date) throws JourneyNotFoundException {
        try {
            return cache.get(new JourneyNumberAndDate(journeyNumber, date.toLocalDate()), () -> {
                // By forcing a number to be passed, we ensure the type is stripped away
                List<Trip> trips = getTripsByJourneyNumber(journeyNumber);
                List<JourneyWithOriginAndDestination> matches = new ArrayList<>();
                // TODO if a time is specified, should the time take precedence to find the correct train "right now"?
                if (shouldConsiderTrainsFromPreviousServiceDaysForQuery(date)) {
                    log.debug("Considering trains from previous service days for journey {} on {}", journeyNumber, date);
                    LocalDate yesterday = date.toLocalDate().minusDays(1);
                    for (Trip trip : trips) {
                        if (!calendarDatesByServiceId.get(trip.serviceId()).contains(yesterday)) {
                            continue;
                        }
                        List<StopTime> stopTimes = stopTimesByTripId.get(trip.id());
                        if (stopTimes.isEmpty()) {
                            continue;
                        }

                        StopTime first = stopTimes.getFirst();
                        StopTime last = stopTimes.getLast();

                        // Departure needs to be after 4, arrival needs to be past midnight,
                        // to count as a desired midnight passing trip
                        if (last.arrivalOffsetSeconds() < SECONDS_IN_DAY || first.departureOffsetSeconds() < SERVICE_DAY_END_HOUR * 3600) {
                            continue; // Trip not active past midnight
                        }

                        Route route = routes.get(trip.routeId());
                        String vehicleType = (route != null) ? route.shortName() : "";
                        log.info("Found trip start and end station for trip {} on day before", journeyNumber);
                        matches.add(new JourneyWithOriginAndDestination(
                                        yesterday,
                                        trip.id(),
                                        vehicleType,
                                        journeyNumber,
                                        first.stopId(),
                                        first.departureOffsetSeconds(),
                                        last.stopId(),
                                        last.arrivalOffsetSeconds(),
                                        last.stopSequence(),
                                        new ArrayList<>()
                                )
                        );
                    }
                    if (!matches.isEmpty()) {
                        return multipleGtfsMatchesToSingleResult(journeyNumber, matches);
                    }
                }

                List<JourneyWithOriginAndDestination> possibleMatches = new ArrayList<>();
                for (Trip trip : trips) {
                    LocalDate activeDate = date.toLocalDate();
                    if (calendarDatesByServiceId.get(trip.serviceId()).contains(activeDate)) {
                        List<StopTime> stopTimes = stopTimesByTripId.get(trip.id());
                        if (stopTimes.isEmpty()) {
                            continue;
                        }

                        StopTime first = stopTimes.getFirst();
                        StopTime last = stopTimes.getLast();

                        Route route = routes.get(trip.routeId());
                        String vehicleType = (route != null) ? route.shortName() : "";

                        possibleMatches.add(new JourneyWithOriginAndDestination(
                                        activeDate,
                                        trip.id(),
                                        vehicleType,
                                        journeyNumber,
                                        first.stopId(),
                                        first.departureOffsetSeconds(),
                                        last.stopId(),
                                        last.arrivalOffsetSeconds(),
                                        last.stopSequence(),
                                        new ArrayList<>()
                                )
                        );
                    }
                }
                if (!possibleMatches.isEmpty()) {
                    return multipleGtfsMatchesToSingleResult(journeyNumber, possibleMatches);
                }
                log.warn("Found no trip start and end station for trip {}", journeyNumber);
                return Optional.empty();
            });
        } catch (UncheckedExecutionException | ExecutionException e) {
            throw new InternalProcessingException("Failed to get trip start and end station: " + e.getMessage(), e);
        }
    }

    private Optional<JourneyWithOriginAndDestination> multipleGtfsMatchesToSingleResult(int journeyNumber, List<JourneyWithOriginAndDestination> possibleMatches) {
        log.debug("Found {} trip start and end stops for trip {}", possibleMatches.size(), journeyNumber);
        boolean containsTrain = possibleMatches.stream().anyMatch(journey -> !journey.getJourneyType().equals("BUS"));
        boolean containsBus = possibleMatches.stream().anyMatch(journey -> journey.getJourneyType().equals("BUS"));
        if (containsBus && containsTrain) {
            possibleMatches.removeIf(journey -> journey.getJourneyType().equals("BUS"));
        }
        log.info("Found {} trip start and end station for trip {} after filtering bus matches", possibleMatches.size(), journeyNumber);
        if (possibleMatches.size() == 1) {
            return Optional.of(possibleMatches.getFirst());
        }

        long suffixedIdCount = possibleMatches.stream()
                .filter(journey -> journey.getTripId().charAt(journey.getTripId().length() - 2) == ':')
                .count();

        // Multiple versions of the same trip, with different lengths: use the longest, mark the shorter start as intermediate
        // Favor trips without a suffix as the better option
        JourneyWithOriginAndDestination longest = possibleMatches.stream()
                .filter(journey -> suffixedIdCount == possibleMatches.size() || !journey.hasSuffixInTripId())
                .max(Comparator.comparingInt(JourneyWithOriginAndDestination::numberOfStops))
                .orElseThrow();
        possibleMatches.remove(longest);
        possibleMatches.stream()
                .sorted(Comparator.comparing(JourneyWithOriginAndDestination::getOriginDepartureTimeOffset))
                .forEach((otherJourney) -> {
                    String longestOrigin = getHafasStationId(longest.getOriginStopId());
                    String longestDestination = getHafasStationId(longest.getDestinationStopId());
                    String otherOrigin = getHafasStationId(otherJourney.getOriginStopId());
                    String otherDestination = getHafasStationId(otherJourney.getDestinationStopId());
                    if (Objects.equals(otherDestination, longestDestination)) {
                        longest.splitOrJoinStopIds().add(otherOrigin);
                    } else {
                        if (Objects.equals(otherOrigin, longestOrigin)) {
                            longest.splitOrJoinStopIds().add(otherDestination);
                        } else {
                            longest.splitOrJoinStopIds().add(otherOrigin);
                            longest.splitOrJoinStopIds().add(otherDestination);
                        }
                    }
                });
        log.info("Selected trip id {} for vehicle {}", longest.tripId(), journeyNumber);
        return Optional.of(longest);
    }

    private String getHafasStationId(String platformStopId) {
        Stop platformStop = stops.get(platformStopId);
        if (platformStop.parentStation() == null) {
            return platformStop.getHafasId();
        }
        return stops.get(platformStop.parentStation()).getHafasId();
    }

    /**
     * Get all successive stops for a vehicle, for use in RIV vehicle search where two non-cancelled points are needed.
     * This method is only needed when one of the first/last stops is cancelled.
     *
     * @param originalJourney The original journey with origin and destination
     * @return List of alternative journey segments between consecutive stops
     */
    public List<JourneyWithOriginAndDestination> getAlternativeVehicleWithOriginAndDestination(JourneyWithOriginAndDestination originalJourney) {
        GtfsInMemoryDao dao = GtfsInMemoryDao.getInstance();
        if (dao == null) {
            return Collections.emptyList();
        }

        List<StopTime> stops = dao.getStopTimesForTrip(originalJourney.getTripId());
        if (stops == null || stops.isEmpty()) {
            return Collections.emptyList();
        }

        // Only search between stops where the train actually stops (pickupType or dropOffType == 0 means passenger exchange)
        List<StopTime> passengerStops = stops.stream()
                .filter(StopTime::hasScheduledPassengerExchange)
                .toList();

        List<JourneyWithOriginAndDestination> results = new ArrayList<>();
        for (int i = 1; i < passengerStops.size(); i++) {
            StopTime prev = passengerStops.get(i - 1);
            StopTime curr = passengerStops.get(i);
            results.add(new JourneyWithOriginAndDestination(
                    originalJourney.tripStartDate(),
                    originalJourney.getTripId(),
                    originalJourney.getJourneyType(),
                    originalJourney.getJourneyNumber(),
                    prev.stopId(),
                    prev.departureOffsetSeconds(),
                    curr.stopId(),
                    curr.arrivalOffsetSeconds(),
                    curr.stopSequence(),
                    Collections.emptyList()
            ));
        }
        return results;
    }

    private record JourneyNumberAndDate(int journeyNumber, LocalDate date) {
    }

    public record CallAtStop(Route route, Trip trip, Stop platform, LocalDate startDate, StopTime stopTime,
                             Stop originParentStop, Stop destinationParentStop) {

    }

    record TripIdAndStartDate(String tripId, LocalDate startDate) {

    }

}
