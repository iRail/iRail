package be.irail.api.gtfs.dao;

import be.irail.api.exception.InternalProcessingException;
import be.irail.api.exception.notfound.JourneyNotFoundException;
import be.irail.api.gtfs.reader.models.Route;
import be.irail.api.gtfs.reader.models.StopTime;
import be.irail.api.gtfs.reader.models.Trip;
import be.irail.api.riv.JourneyWithOriginAndDestination;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.util.concurrent.UncheckedExecutionException;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Component;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.*;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

@Component
public class GtfsTripStartEndExtractor {
    private static final Logger log = LogManager.getLogger(GtfsTripStartEndExtractor.class);
    public static final int SECONDS_IN_DAY = 86400;
    public static final int SERVICE_DAY_END_HOUR = 4;

    private record JourneyNumberAndDate(int journeyNumber, LocalDate date) {
    }

    private final Cache<JourneyNumberAndDate, Optional<JourneyWithOriginAndDestination>> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(4, TimeUnit.HOURS)
            .build();

    public Optional<LocalDate> getStartDate(int journeyNumber, LocalDateTime plannedDateTime) throws JourneyNotFoundException {
        Optional<JourneyWithOriginAndDestination> journey = getVehicleWithOriginAndDestination(journeyNumber, plannedDateTime);
        return journey.map(JourneyWithOriginAndDestination::tripStartDate);
    }

    public Optional<JourneyWithOriginAndDestination> getVehicleWithOriginAndDestination(int journeyNumber, LocalDateTime date) throws JourneyNotFoundException {
        try {
            return cache.get(new JourneyNumberAndDate(journeyNumber, date.toLocalDate()), () -> {
                GtfsInMemoryDao dao = GtfsInMemoryDao.getInstance();
                if (dao == null) {
                    return Optional.empty();
                }
                // By forcing a number to be passed, we ensure the type is stripped away
                List<Trip> trips = dao.getTripsByJourneyNumber(journeyNumber);
                List<JourneyWithOriginAndDestination> matches = new ArrayList<>();
                // TODO if a time is specified, should the time take precedence to find the correct train "right now"?
                if (shouldConsiderTrainsFromPreviousServiceDaysForQuery(date)) {
                    log.debug("Considering trains from previous service days for journey {} on {}", journeyNumber, date);
                    LocalDate yesterday = date.toLocalDate().minusDays(1);
                    for (Trip trip : trips) {
                        if (!isServiceActiveOnDate(dao, trip.serviceId(), yesterday)) {
                            continue;
                        }
                        List<StopTime> stopTimes = dao.getStopTimesForTrip(trip.id());
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

                        Route route = dao.getRoute(trip.routeId());
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
                    if (isServiceActiveOnDate(dao, trip.serviceId(), activeDate)) {
                        List<StopTime> stopTimes = dao.getStopTimesForTrip(trip.id());
                        if (stopTimes.isEmpty()) {
                            continue;
                        }

                        StopTime first = stopTimes.getFirst();
                        StopTime last = stopTimes.getLast();

                        Route route = dao.getRoute(trip.routeId());
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

    private static Optional<JourneyWithOriginAndDestination> multipleGtfsMatchesToSingleResult(int journeyNumber, List<JourneyWithOriginAndDestination> possibleMatches) {
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
                    String longestOrigin = getBaseStop(longest.getOriginStopId());
                    String longestDestination = getBaseStop(longest.getDestinationStopId());
                    String otherOrigin = getBaseStop(otherJourney.getOriginStopId());
                    String otherDestination = getBaseStop(otherJourney.getDestinationStopId());
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

    private static String getBaseStop(String stopId) {
        return stopId.replaceAll("_\\d+", "");
    }

    private static boolean shouldConsiderTrainsFromPreviousServiceDaysForQuery(LocalDateTime date) {
        return date.getHour() < SERVICE_DAY_END_HOUR && date.toLocalDate().equals(LocalDate.now());
    }

    private boolean isServiceActiveOnDate(GtfsInMemoryDao dao, Integer serviceId, LocalDate date) {
        Set<LocalDate> dates = dao.getCalendarDates(serviceId);
        return dates.contains(date);
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
}
