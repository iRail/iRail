package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import be.irail.api.gtfs.reader.models.Stop;

import java.time.Instant;
import java.time.LocalDate;
import java.util.*;

/**
 * In-memory DAO for GTFS-Realtime delay data.
 */
public class GtfsRtInMemoryDao {
    private static final GtfsRtInMemoryDao INSTANCE = new GtfsRtInMemoryDao();

    private volatile Snapshot snapshot = Snapshot.empty();

    private GtfsRtInMemoryDao() {
    }

    public static GtfsRtInMemoryDao getInstance() {
        return INSTANCE;
    }

    /**
     * Atomically replaces all data belonging to one GTFS-RT feed version.
     *
     * @param delays the list of new delays
     * @param canceledTrips trips cancelled by this feed version
     * @param version the GTFS-RT feed header timestamp
     */
    public void update(List<GtfsRtUpdate> delays, Set<DatedTripId> canceledTrips, Instant version) {
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
        snapshot = new Snapshot(immutableNestedMap(tripMap), immutableNestedMap(stopMap),
                Set.copyOf(canceledTrips), version);
    }

    /**
     * Updates delay data independently. Prefer {@link #update(List, Set, Instant)}
     * when processing a complete feed.
     */
    public void updateStopTimeUpdates(List<GtfsRtUpdate> delays) {
        Instant version = delays.stream()
                .map(GtfsRtUpdate::timestamp)
                .filter(Objects::nonNull)
                .map(java.time.OffsetDateTime::toInstant)
                .max(Comparator.naturalOrder())
                .orElse(null);
        update(delays, snapshot.canceledTrips(), version);
    }

    public void updateCanceledTrips(Set<DatedTripId> canceledTrips) {
        Snapshot current = snapshot;
        snapshot = new Snapshot(current.updatesByTripIdAndStop(), current.updatesByStopIdAndTrip(),
                Set.copyOf(canceledTrips), current.version());
    }

    public Snapshot getSnapshot() {
        return snapshot;
    }

    public Map<String, GtfsRtUpdate> getUpdatesByTripId(String tripId) {
        return snapshot.getUpdatesByTripId(tripId);
    }

    public Map<String, GtfsRtUpdate> getUpdatesByHafasStopId(String stopId) {
        return snapshot.updatesByStopIdAndTrip().get(stopId);
    }

    public boolean isCanceled(String tripId, LocalDate startDate) {
        return snapshot.isCanceled(tripId, startDate);
    }

    private static Map<String, Map<String, GtfsRtUpdate>> immutableNestedMap(
            Map<String, Map<String, GtfsRtUpdate>> source) {
        Map<String, Map<String, GtfsRtUpdate>> copy = new HashMap<>();
        source.forEach((key, value) -> copy.put(key, Map.copyOf(value)));
        return Map.copyOf(copy);
    }

    /** One immutable and internally consistent GTFS-RT feed generation. */
    public record Snapshot(
            Map<String, Map<String, GtfsRtUpdate>> updatesByTripIdAndStop,
            Map<String, Map<String, GtfsRtUpdate>> updatesByStopIdAndTrip,
            Set<DatedTripId> canceledTrips,
            Instant version
    ) {
        private static Snapshot empty() {
            return new Snapshot(Map.of(), Map.of(), Set.of(), null);
        }

        public Map<String, GtfsRtUpdate> getUpdatesByTripId(String tripId) {
            return updatesByTripIdAndStop.getOrDefault(tripId, Map.of());
        }

        public boolean isCanceled(String tripId, LocalDate startDate) {
            return canceledTrips.contains(new DatedTripId(tripId, startDate));
        }
    }
}
