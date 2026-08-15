package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import be.irail.api.gtfs.reader.models.Stop;

import java.time.Duration;
import java.time.Instant;
import java.time.LocalDate;
import java.util.*;

/**
 * In-memory DAO for GTFS-Realtime delay data.
 */
public class GtfsRtInMemoryDao {
    private static final GtfsRtInMemoryDao INSTANCE = new GtfsRtInMemoryDao();

    /** Maximum age of realtime data before it is ignored and departures fall back to schedule-only. */
    private static final Duration MAX_STALENESS = Duration.ofMinutes(5);

    private Map<String, Map<String, GtfsRtUpdate>> updatesByTripIdAndStop = new HashMap<>();
    private Map<String, Map<String, GtfsRtUpdate>> updatesByStopIdAndTrip = new HashMap<>();
    private Set<DatedTripId> canceledTrips = new HashSet<>();

    /** Header timestamp of the last applied feed; used to stop serving stale realtime data. */
    private volatile Instant lastFeedTimestamp;

    private GtfsRtInMemoryDao() {
    }

    public static GtfsRtInMemoryDao getInstance() {
        return INSTANCE;
    }

    /**
     * Updates the in-memory store with new GTFS-RT delay data.
     *
     * @param delays the list of new delays
     */
    public void updateStopTimeUpdates(List<GtfsRtUpdate> delays) {
        Map<String, Map<String, GtfsRtUpdate>> tripMap = new HashMap<>();
        Map<String, Map<String, GtfsRtUpdate>> stopMap = new HashMap<>();

        for (GtfsRtUpdate update : delays) {
            tripMap.putIfAbsent(update.tripId(), new HashMap<>());
            tripMap.get(update.tripId()).put(update.stopId(), update);
            String stopId = Stop.getHafasId(update.parentStopId());
            if (stopId == null) {
                // International parent stops can be name-based; their platform
                // stop often still carries the numeric station identifier.
                stopId = Stop.getHafasId(update.stopId());
            }
            if (stopId != null) {
                stopMap.putIfAbsent(stopId, new HashMap<>());
                stopMap.get(stopId).put(update.tripId(), update);
            }
        }
        // Atomic update of the maps (replace content)
        updatesByTripIdAndStop = tripMap;
        updatesByStopIdAndTrip = stopMap;
    }

    public void updateCanceledTrips(Set<DatedTripId> canceledTrips) {
        this.canceledTrips = canceledTrips;
    }

    /** Records the header timestamp of the feed just applied, marking the realtime data fresh. */
    public void setFeedTimestamp(Instant feedTimestamp) {
        this.lastFeedTimestamp = feedTimestamp;
    }

    /** True when no feed has been applied yet, or the last one is older than MAX_STALENESS. */
    private boolean isStale() {
        Instant ts = lastFeedTimestamp;
        return ts == null || Instant.now().isAfter(ts.plus(MAX_STALENESS));
    }

    public Map<String, GtfsRtUpdate> getUpdatesByTripId(String tripId) {
        if (isStale()) {
            return new HashMap<>();
        }
        return updatesByTripIdAndStop.getOrDefault(tripId, new HashMap<>());
    }

    public Map<String, GtfsRtUpdate> getUpdatesByHafasStopId(String stopId) {
        if (isStale()) {
            return null;
        }
        return updatesByStopIdAndTrip.get(stopId);
    }

    public boolean isCanceled(String tripId, LocalDate startDate) {
        if (isStale()) {
            return false;
        }
        return canceledTrips.contains(new DatedTripId(tripId, startDate));
    }
}
