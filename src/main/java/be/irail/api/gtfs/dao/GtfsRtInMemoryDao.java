package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtDelay;
import com.google.common.collect.ArrayListMultimap;

import java.time.LocalDate;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * In-memory DAO for GTFS-Realtime delay data.
 */
public class GtfsRtInMemoryDao {
    private static final GtfsRtInMemoryDao INSTANCE = new GtfsRtInMemoryDao();

    private ArrayListMultimap<String, GtfsRtDelay> delaysByStopId = ArrayListMultimap.create();
    private ArrayListMultimap<String, GtfsRtDelay> updatesByStopId = ArrayListMultimap.create();
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
    public void updateStopTimeUpdates(List<GtfsRtDelay> delays) {
        ArrayListMultimap<String, GtfsRtDelay> tripMap = ArrayListMultimap.create();
        ArrayListMultimap<String, GtfsRtDelay> stopMap = ArrayListMultimap.create();

        for (GtfsRtDelay delay : delays) {
            tripMap.put(delay.tripId(), delay);
            if (delay.parentStopId() != null) {
                stopMap.put(delay.parentStopId(), delay);
            }
        }
        // Atomic update of the maps (replace content)
        delaysByStopId = tripMap;
        updatesByStopId = stopMap;
    }

    public void updateCanceledTrips(Set<DatedTripId> canceledTrips) {
        this.canceledTrips = canceledTrips;
    }

    public List<GtfsRtDelay> getDelaysByTripId(String tripId) {
        return delaysByStopId.get(tripId);
    }

    public boolean isCanceled(String tripId, LocalDate startDate) {
        return canceledTrips.contains(new DatedTripId(tripId, startDate));
    }

    public List<GtfsRtDelay> getDelaysByStopId(String stopId) {
        return updatesByStopId.get(stopId);
    }
}
