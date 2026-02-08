package be.irail.api.gtfs.dao;

import be.irail.api.exception.InternalProcessingException;
import be.irail.api.gtfs.reader.models.Route;
import be.irail.api.gtfs.reader.models.StopTime;
import be.irail.api.gtfs.reader.models.Trip;
import be.irail.api.riv.JourneyWithOriginAndDestination;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import org.springframework.stereotype.Component;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Set;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

@Component
public class GtfsTripStartEndExtractor {

    private record JourneyNumberAndDate(int journeyNumber, LocalDate date) {
    }

    private final Cache<JourneyNumberAndDate, JourneyWithOriginAndDestination> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(4, TimeUnit.HOURS)
            .build();

    public LocalDate getStartDate(int journeyNumber, LocalDateTime plannedDateTime) {
        JourneyWithOriginAndDestination journey = getVehicleWithOriginAndDestination(journeyNumber, plannedDateTime);
        if (journey != null) {
            // First stop departure time is in seconds from midnight of the start date
            return plannedDateTime.toLocalDate();
        }
        return null;
    }

    public JourneyWithOriginAndDestination getVehicleWithOriginAndDestination(int journeyNumber, LocalDateTime date) {
        try {
            return cache.get(new JourneyNumberAndDate(journeyNumber, date.toLocalDate()), () -> {
                GtfsInMemoryDao dao = GtfsInMemoryDao.getInstance();
                if (dao == null) {
                    return null;
                }
                // By forcing a number to be passed, we ensure the type is stripped away
                List<Trip> trips = dao.getTripsByJourneyNumber(journeyNumber);

                for (Trip trip : trips) {
                    if (isServiceActiveOnDate(dao, trip.serviceId(), date.toLocalDate())) {
                        List<StopTime> stopTimes = dao.getStopTimesForTrip(trip.id());
                        if (stopTimes.isEmpty()) {
                            continue;
                        }

                        StopTime first = stopTimes.getFirst();
                        StopTime last = stopTimes.getLast();

                        Route route = dao.getRoute(trip.routeId());
                        String vehicleType = (route != null) ? route.shortName() : "";

                        return new JourneyWithOriginAndDestination(
                                trip.id(),
                                vehicleType,
                                journeyNumber,
                                first.stopId(),
                                first.departureTime(),
                                last.stopId(),
                                last.arrivalTime(),
                                Collections.emptyList()
                        );
                    }
                }

                return null;
            });
        } catch (ExecutionException e) {
            throw new InternalProcessingException("Failed to get trip start and end station: " + e.getMessage(), e);
        }
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
                .filter(stop -> stop.pickupType() == 0 || stop.dropOffType() == 0)
                .toList();

        List<JourneyWithOriginAndDestination> results = new ArrayList<>();
        for (int i = 1; i < passengerStops.size(); i++) {
            StopTime prev = passengerStops.get(i - 1);
            StopTime curr = passengerStops.get(i);
            results.add(new JourneyWithOriginAndDestination(
                    originalJourney.getTripId(),
                    originalJourney.getJourneyType(),
                    originalJourney.getJourneyNumber(),
                    prev.stopId(),
                    prev.departureTime(),
                    curr.stopId(),
                    curr.arrivalTime(),
                    Collections.emptyList()
            ));
        }
        return results;
    }
}
