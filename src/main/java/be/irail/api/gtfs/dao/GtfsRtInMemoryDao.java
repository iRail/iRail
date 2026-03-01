package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;

import java.time.LocalDate;
import java.util.*;

/**
 * In-memory DAO for GTFS-Realtime delay data.
 */
public class GtfsRtInMemoryDao {
    private static final GtfsRtInMemoryDao INSTANCE = new GtfsRtInMemoryDao();

    private Map<String, Map<String, GtfsRtUpdate>> updatesByTripIdAndStop = new HashMap<>();
    private Map<String, Map<String, GtfsRtUpdate>> updatesByStopIdAndTrip = new HashMap<>();
    private Set<DatedTripId> canceledTrips = new HashSet<>();

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
            if (update.parentStopId() != null) {
                // Remove the "S" prefix for station type stops
                String stopId = update.parentStopId().substring(1);
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

    public Map<String, GtfsRtUpdate> getUpdatesByTripId(String tripId) {
        return updatesByTripIdAndStop.getOrDefault(tripId, new HashMap<>());
    }

    public Map<String, GtfsRtUpdate> getUpdatesByHafasStopId(String stopId) {
        return updatesByStopIdAndTrip.get(stopId);
    }

    public boolean isCanceled(String tripId, LocalDate startDate) {
        return canceledTrips.contains(new DatedTripId(tripId, startDate));
    }
}
