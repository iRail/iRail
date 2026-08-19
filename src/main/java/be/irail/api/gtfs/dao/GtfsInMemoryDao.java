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
    private static final int GTFS_LOCATION_TYPE_STATION = 1;
    private static final String TRIP_ID_NAMESPACE_PREFIX = "gt:";

    private static volatile GtfsInMemoryDao instance = null;

    private final Map<String, Agency> agencies;
    private final HashMultimap<String, LocalDate> calendarDatesByServiceId;
    private final HashMultimap<LocalDate, TripIdAndStartDate> tripIdsByDate;
    private final HashMultimap<LocalDate, TripIdAndStartDate> tripsStartedAtPreviousDayByDate;
    private final Map<String, Route> routes;
    private final Map<String, Stop> stops;
    private final ArrayListMultimap<String, StopTime> stopTimesByTripId;
    private final ArrayListMultimap<String, StopTime> stopTimesByStopId;
    private final ArrayListMultimap<Integer, Trip> tripsByShortName;
    private final HashMap<String, Trip> tripsById;
    private final HashMultimap<String, String> tripIdsByIdBody;
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
        data.calendarDates().forEach(cd -> calendarDatesByServiceId.put(cd.serviceId(), cd.date()));

        this.routes = new HashMap<>();
        data.routes().forEach(r -> routes.put(r.id(), r));

        this.stops = new HashMap<>();
        data.stops().forEach(s -> stops.put(s.id(), s));

        this.tripsByShortName = ArrayListMultimap.create();
        this.tripIdsByDate = HashMultimap.create();
        this.tripsById = new HashMap<>();
        this.tripIdsByIdBody = HashMultimap.create();
        data.trips().forEach(t -> {
            tripsByShortName.put(t.shortName(), t);
            calendarDatesByServiceId.get(t.serviceId()).forEach(date -> tripIdsByDate.put(date, new TripIdAndStartDate(t.id(), date)));
            tripsById.put(t.id(), t);
            tripIdsByIdBody.put(tripIdMatchKey(t.id()), t.id());
        });

        this.stopTimesByTripId = ArrayListMultimap.create();
        this.stopTimesByStopId = ArrayListMultimap.create();
        this.tripsStartedAtPreviousDayByDate = HashMultimap.create();
        data.stopTimes().forEach(stopTime -> {
            stopTimesByTripId.put(stopTime.tripId(), stopTime);
            Stop platformStop = stops.get(stopTime.stopId());
            if (platformStop == null) {
                log.warn("Stop time references unknown GTFS stop {}", stopTime.stopId());
                return;
            }
            String hafasStopId = getHafasStationId(platformStop.id());
            if (hafasStopId != null) {
                stopTimesByStopId.put(hafasStopId, stopTime);
            }
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

    /**
     * Get all stops of the Station type.
     *
     * @return all stops of the Station type.
     */
    public List<Stop> getAllStations() {
        return stops.values().stream().filter(stop -> stop.locationType() == GTFS_LOCATION_TYPE_STATION).toList();
    }

    public List<Trip> getTripsByJourneyNumber(Integer shortName) {
        return tripsByShortName.get(shortName);
    }

    public List<StopTime> getStopTimesForTrip(String tripId) {
        return stopTimesByTripId.get(tripId);
    }

    public Set<LocalDate> getCalendarDates(String serviceId) {
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

    /**
     * Strips the trailing date component from a GTFS trip id.
     *
     * <p>NMBS trip ids end in a date, e.g.
     * {@code gt:nmbssncb:88____:UUU::8775100:8814001:8:1834:20260810}. That date is not the service
     * date: the static feed uses it to tell apart service patterns of the same run, while
     * GTFS-Realtime puts the actual service date there. The part before it is stable across both
     * feeds, so it is what the two can be matched on.
     *
     * @param tripId a GTFS trip id
     * @return the trip id without its trailing {@code :date} component, or the id unchanged if it has none
     */
    public static String tripIdBody(String tripId) {
        int lastSeparator = tripId.lastIndexOf(':');
        return lastSeparator < 0 ? tripId : tripId.substring(0, lastSeparator);
    }

    /**
     * Builds the key on which realtime and scheduled trip ids can be compared.
     *
     * <p>On top of the trailing date (see {@link #tripIdBody(String)}) the two feeds disagree on the
     * leading namespace: the Belgian Mobility feeds prefix ids with {@code gt:<agency>:} while the
     * hafas feed at {@code sncb-opendata.hafas.de} omits it entirely. Dropping that prefix is what
     * lets a realtime trip from either source be looked up in the same index.
     *
     * @param tripId a trip id from either feed
     * @return the id without a leading {@code gt:<agency>:} namespace and without its trailing date
     */
    public static String tripIdMatchKey(String tripId) {
        String withoutDate = tripIdBody(tripId);
        if (!withoutDate.startsWith(TRIP_ID_NAMESPACE_PREFIX)) {
            return withoutDate;
        }
        int agencySeparator = withoutDate.indexOf(':', TRIP_ID_NAMESPACE_PREFIX.length());
        return agencySeparator < 0 ? withoutDate : withoutDate.substring(agencySeparator + 1);
    }

    /**
     * Resolves a GTFS-Realtime trip id to the static trip id of the run operating on a given date.
     *
     * <p>Comparing realtime and static trip ids directly almost never matches, because the two feeds
     * put different dates in the trailing component (see {@link #tripIdBody(String)}). Instead the
     * candidates sharing a trip id body are narrowed down to the one whose service actually runs on
     * {@code serviceDate}. In the current NMBS feed that leaves at most one candidate for all but a
     * handful of the 14 408 bodies that have several; those remaining ties are broken on the lowest
     * trip id so the result stays stable between feed refreshes.
     *
     * @param tripId      a trip id from either feed
     * @param serviceDate the date the trip is running on
     * @return the matching static trip id, or empty when no known trip runs that day
     */
    public Optional<String> resolveTripIdForServiceDate(String tripId, LocalDate serviceDate) {
        if (tripsById.containsKey(tripId)) {
            return Optional.of(tripId);
        }
        return tripIdsByIdBody.get(tripIdMatchKey(tripId)).stream()
                .filter(candidate -> runsOn(candidate, serviceDate))
                .min(Comparator.naturalOrder());
    }

    private boolean runsOn(String tripId, LocalDate serviceDate) {
        Trip trip = tripsById.get(tripId);
        return trip != null && calendarDatesByServiceId.get(trip.serviceId()).contains(serviceDate);
    }

    public Stop getStop(StopTime stopTime, LocalDate startDate) {
        return stops.get(stopTime.stopId());
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
                                stationStop(originStop, originParentStop),
                                stationStop(destinationStop, destinationParentStop)));
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
        if (platformStop == null) {
            return null;
        }
        Stop parentStop = platformStop.parentStation() == null ? null : stops.get(platformStop.parentStation());
        Stop stationStop = stationStop(platformStop, parentStop);
        return stationStop.getHafasId();
    }

    private Stop stationStop(Stop platformStop, Stop parentStop) {
        return parentStop != null && parentStop.getHafasId() != null ? parentStop : platformStop;
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

        List<StopTime> stopsForTrip = dao.getStopTimesForTrip(originalJourney.getTripId());
        if (stopsForTrip == null || stopsForTrip.isEmpty()) {
            return Collections.emptyList();
        }

        // Only search between stops where the train actually stops (pickupType or dropOffType == 0 means passenger exchange)
        List<StopTime> passengerStops = stopsForTrip.stream()
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
