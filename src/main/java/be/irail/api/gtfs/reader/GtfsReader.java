package be.irail.api.gtfs.reader;

import be.irail.api.dto.Language;
import be.irail.api.gtfs.reader.entities.StopTimeOverrideEntity;
import be.irail.api.gtfs.reader.entities.TranslationEntity;
import be.irail.api.gtfs.reader.models.*;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.onebusaway.gtfs.impl.GtfsDaoImpl;
import org.onebusaway.gtfs.model.ServiceCalendar;
import org.onebusaway.gtfs.model.ShapePoint;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.URI;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

/**
 * Service for reading static GTFS data.
 */
@Component
public class GtfsReader {

    private static final Logger log = LogManager.getLogger(GtfsReader.class);

    private final String gtfsStaticUrl;
    private final int readDaysBack;
    private final int readDaysForward;

    @Autowired
    public GtfsReader(@Value("${gtfs.static.url}") String gtfsUrl,
                      @Value("${gtfs.static.range.back}") int readDaysBack,
                      @Value("${gtfs.static.range.forward}") int readDaysForward) {
        this.gtfsStaticUrl = gtfsUrl;
        this.readDaysBack = readDaysBack;
        this.readDaysForward = readDaysForward;
    }

    public GtfsData readGtfs() {
        log.info("Fetching static GTFS from {}, requires around 1gb free memory, current heap usage/max heap usage: {}/{}",
                gtfsStaticUrl, Runtime.getRuntime().totalMemory() / 1024 / 1024, Runtime.getRuntime().maxMemory() / 1024 / 1024);
        Path tempFile = null;
        try {
            tempFile = Files.createTempFile("gtfs-static", ".zip");
            URL url = URI.create(gtfsStaticUrl).toURL();
            try (InputStream in = url.openStream();
                 OutputStream out = Files.newOutputStream(tempFile)) {
                in.transferTo(out);
            }

            GtfsDaoImpl dao = new GtfsDaoImpl();
            org.onebusaway.gtfs.serialization.GtfsReader reader = new org.onebusaway.gtfs.serialization.GtfsReader();
            reader.getEntityClasses().add(StopTimeOverrideEntity.class);
            reader.getEntityClasses().add(TranslationEntity.class);
            // don't read shapes, in case NMBS would add them suddenly, as they are typically huge
            reader.getEntityClasses().remove(ShapePoint.class);
            // Service calendars don't contain any information, everything is present in calendar_dates.
            reader.getEntityClasses().remove(ServiceCalendar.class);
            // Don't read transfers, they aren't used in the application right now
            reader.getEntityClasses().remove(org.onebusaway.gtfs.model.Transfer.class);
            reader.setInputLocation(tempFile.toFile());
            reader.setEntityStore(dao);
            reader.run();
            reader.close();
            log.info("Finished reading GTFS data, mapping to internal model. Current heap memory usage {}",
                    Runtime.getRuntime().totalMemory() / 1024 / 1024);
            GtfsData gtfsData = mapToInternalModel(dao);
            log.info("Finished parsing and converting GTFS data");
            return gtfsData;
        } catch (IOException e) {
            log.error("Failed to read GTFS data", e);
            return null;
        } finally {
            if (tempFile != null) {
                try {
                    Files.deleteIfExists(tempFile);
                } catch (IOException e) {
                    log.warn("Failed to delete temp GTFS file", e);
                }
            }
        }
    }

    private GtfsData mapToInternalModel(GtfsDaoImpl dao) {
        List<TranslationEntity> translationEntities = dao.getAllEntitiesForType(TranslationEntity.class).stream()
                .toList();

        // We use the translation data to add translations to stops, then discard the original translation objects to save memory
        Map<String, Map<Language, String>> translationsByStopId = new HashMap<>();
        Map<String, Map<Language, String>> translationsByHeadsign = new HashMap<>();
        for (TranslationEntity te : translationEntities) {
            if ("stops".equals(te.getTableName()) && "stop_name".equals(te.getFieldName())) {
                Language lang = Language.fromString(te.getLanguage());
                if (lang != null) {
                    translationsByStopId.computeIfAbsent(te.getFieldValue(), k -> new HashMap<>()).put(lang, te.getTranslation());
                }
            }
            if ("trips".equals(te.getTableName()) && "trip_headsign".equals(te.getFieldName())) {
                Language lang = Language.fromString(te.getLanguage());
                if (lang != null) {
                    translationsByHeadsign.computeIfAbsent(te.getFieldValue(), k -> new HashMap<>()).put(lang, te.getTranslation());
                }
            }
        }

        List<Agency> agencies = dao.getAllAgencies().stream()
                .map(a -> new Agency(a.getId() != null ? a.getId() : "", a.getName(), a.getUrl(), a.getTimezone(), a.getLang(), a.getPhone(), a.getFareUrl(), null))
                .toList();

        LocalDate firstDate = LocalDate.now().minusDays(readDaysBack);
        LocalDate lastdate = LocalDate.now().plusDays(readDaysForward);
        List<CalendarDate> calendarDates = dao.getAllCalendarDates().stream()
                .filter(cd -> cd.getExceptionType() == 1) // Only ADDED days
                .map(cd -> new CalendarDate(Integer.parseInt(cd.getServiceId().getId()),
                        LocalDate.of(cd.getDate().getYear(), cd.getDate().getMonth(), cd.getDate().getDay())))
                .filter(cd -> !cd.date().isBefore(firstDate) && !cd.date().isAfter(lastdate))
                .toList();
        log.info("Read {} calendar dates, kept {} within date range", dao.getAllCalendarDates().size(), calendarDates.size());
        Set<Integer> usedServiceIds = calendarDates.stream().map(CalendarDate::serviceId).collect(java.util.stream.Collectors.toSet());

        List<Trip> trips = dao.getAllTrips().stream()
                .map(t -> new Trip(t.getId().getId(), t.getRoute().getId().getId(), Integer.parseInt(t.getServiceId().getId()), t.getTripHeadsign(), Integer.parseInt(t.getTripShortName()), t.getDirectionId() != null ? Integer.parseInt(t.getDirectionId()) : 0, t.getBlockId()))
                .filter(t -> usedServiceIds.contains(t.serviceId()))
                .toList();
        log.info("Read {} trips, kept {} within date range", dao.getAllTrips().size(), trips.size());
        Set<String> usedTripIds = trips.stream().map(Trip::id).collect(java.util.stream.Collectors.toSet());
        Set<String> usedRouteIds = trips.stream().map(Trip::routeId).collect(java.util.stream.Collectors.toSet());

        List<Route> routes = dao.getAllRoutes().stream()
                .map(r -> new Route(r.getId().getId(), r.getAgency() != null ? r.getAgency().getId() : null, r.getShortName(), r.getLongName(), r.getDesc(), r.getType()))
                .filter(r -> usedRouteIds.contains(r.id()))
                .toList();

        List<StopTime> stopTimes = dao.getAllStopTimes().stream()
                .filter(st -> usedTripIds.contains(st.getTrip().getId().getId()))
                .map(st -> new StopTime(st.getTrip().getId().getId(), st.getArrivalTime(), st.getDepartureTime(), st.getStop().getId().getId(), st.getStopSequence(), st.getStopHeadsign(), st.getPickupType(), st.getDropOffType()))
                .toList();
        log.info("Read {} stop times, kept {} within date range", dao.getAllStopTimes().size(), stopTimes.size());
        Map<TripIdAndSequence, StopTime> stopTimesByTrip = stopTimes.stream().collect(Collectors.toMap(st -> new TripIdAndSequence(st.tripId(), st.stopSequence()), st -> st));

        List<StopTimeOverrideEntity> overrides = dao.getAllEntitiesForType(StopTimeOverrideEntity.class).stream()
                .filter(sto -> usedTripIds.contains(sto.getTripId()) && usedServiceIds.contains(Integer.parseInt(sto.getServiceId())))
                .toList();
        for (StopTimeOverrideEntity sto : overrides) {
            stopTimesByTrip.get(new TripIdAndSequence(sto.getTripId(), sto.getStopSequence())).addOverride(Integer.parseInt(sto.getServiceId()), sto.getStopId());
        }
        log.info("Read {} stop time overrides, kept {} within date range", dao.getAllEntitiesForType(StopTimeOverrideEntity.class).size(), overrides.size());

        FeedInfo feedInfo = dao.getAllFeedInfos().stream()
                .map(f -> new FeedInfo(f.getPublisherName(), f.getPublisherUrl(), f.getLang(),
                        f.getStartDate() != null ? LocalDate.of(f.getStartDate().getYear(), f.getStartDate().getMonth(), f.getStartDate().getDay()) : null,
                        f.getEndDate() != null ? LocalDate.of(f.getEndDate().getYear(), f.getEndDate().getMonth(), f.getEndDate().getDay()) : null,
                        f.getVersion(), null, null))
                .findFirst().get();

        List<Stop> stops = dao.getAllStops().stream()
                .map(s -> {
                    Map<Language, String> localNames = new HashMap<>(translationsByStopId.getOrDefault(s.getName(), Map.of()));
                    localNames.putIfAbsent(Language.FR, s.getName());
                    return new Stop(s.getId().getId(), s.getCode(), s.getName(), s.getDesc(), s.getLat(), s.getLon(), s.getZoneId(), s.getUrl(), s.getLocationType(), s.getParentStation(), s.getTimezone(), s.getWheelchairBoarding(), null, s.getPlatformCode(), localNames);
                })
                .toList();

        return new GtfsData(feedInfo, agencies, calendarDates, routes, stops, stopTimes, overrides, trips);
    }

    private int parseTime(String time) {
        if (time == null || time.isBlank()) {
            return -1;
        }
        String[] hms = time.split(":");
        return Integer.parseInt(hms[0]) * 3600 + Integer.parseInt(hms[1]) * 60 + Integer.parseInt(hms[2]);
    }

    public record GtfsData(
            FeedInfo feedInfos,
            List<Agency> agencies,
            List<CalendarDate> calendarDates,
            List<Route> routes,
            List<Stop> stops,
            List<StopTime> stopTimes,
            List<StopTimeOverrideEntity> stopTimeOverrides,
            List<Trip> trips
    ) {
    }

    private record TripIdAndSequence(String tripId, int sequenceNr) {
    }

}
