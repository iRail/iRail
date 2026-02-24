package be.irail.api.gtfs.reader;

import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.reader.models.GtfsRtDelay;
import be.irail.api.gtfs.reader.models.Stop;
import com.google.transit.realtime.GtfsRealtime;
import com.google.transit.realtime.GtfsRealtime.FeedEntity;
import com.google.transit.realtime.GtfsRealtime.FeedMessage;
import com.google.transit.realtime.GtfsRealtime.TripUpdate;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.time.Instant;
import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.ZoneOffset;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * Service that periodically updates GTFS-Realtime data.
 */
@Service
public class GtfsRtUpdater {

    private static final Logger log = LogManager.getLogger(GtfsRtUpdater.class);
    public static final DateTimeFormatter DATEFORMAT_YYYYMMDD = DateTimeFormatter.ofPattern("yyyyMMdd");

    private final GtfsRtReader gtfsRtReader;

    public GtfsRtUpdater(GtfsRtReader gtfsRtReader) {
        this.gtfsRtReader = gtfsRtReader;
    }

    /**
     * Periodically fetches and processes GTFS-Realtime TripUpdates.
     * Runs every 15 seconds.
     */
    @Scheduled(fixedRate = 15000)
    public void update() {
        GtfsInMemoryDao staticDao = GtfsInMemoryDao.getInstance();
        if (staticDao == null) {
            log.warn("GtfsInMemoryDao instance is not ready. Skipping GTFS-RT update.");
            return;
        }

        FeedMessage feed = gtfsRtReader.readTripUpdates();
        if (feed == null) {
            return;
        }

        List<GtfsRtDelay> delays = new ArrayList<>();
        OffsetDateTime timestamp = OffsetDateTime.ofInstant(Instant.ofEpochSecond(feed.getHeader().getTimestamp()), ZoneOffset.UTC);
        Set<DatedTripId> canceledTrips = new HashSet<>();

        for (FeedEntity entity : feed.getEntityList()) {
            if (entity.hasTripUpdate()) {
                TripUpdate tu = entity.getTripUpdate();
                String tripId = tu.getTrip().getTripId();
                LocalDate startDate = LocalDate.parse(tu.getTrip().getStartDate(), DATEFORMAT_YYYYMMDD);

                for (TripUpdate.StopTimeUpdate stu : tu.getStopTimeUpdateList()) {
                    String stopId = stu.getStopId();
                    int arrivalDelay = stu.hasArrival() ? stu.getArrival().getDelay() : 0;
                    int departureDelay = stu.hasDeparture() ? stu.getDeparture().getDelay() : 0;

                    Stop stop = staticDao.getStop(stopId);
                    String parentStopId = (stop != null) ? stop.parentStation() : null;

                    delays.add(new GtfsRtDelay(startDate, tripId, stopId, parentStopId, arrivalDelay, departureDelay, timestamp));
                }
                if (tu.getTrip().getScheduleRelationship() == GtfsRealtime.TripDescriptor.ScheduleRelationship.CANCELED) {
                    canceledTrips.add(new DatedTripId(tripId, startDate));
                }
            }
        }

        GtfsRtInMemoryDao.getInstance().updateCanceledTrips(canceledTrips);
        GtfsRtInMemoryDao.getInstance().updateStopTimeUpdates(delays);
        log.info("Updated GTFS-RT with {} delay records", delays.size());
    }
}
