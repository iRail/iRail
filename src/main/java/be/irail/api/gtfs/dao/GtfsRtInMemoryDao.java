package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.models.GtfsRtDelay;
import com.google.common.collect.ArrayListMultimap;

import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 * In-memory DAO for GTFS-Realtime delay data.
 */
public class GtfsRtInMemoryDao {
    private static final GtfsRtInMemoryDao INSTANCE = new GtfsRtInMemoryDao();

    private final Map<String, List<GtfsRtDelay>> delaysByTripId = new ConcurrentHashMap<>();
    private final Map<String, List<GtfsRtDelay>> delaysByStopId = new ConcurrentHashMap<>();

    private GtfsRtInMemoryDao() {}

    public static GtfsRtInMemoryDao getInstance() {
        return INSTANCE;
    }

    /**
     * Updates the in-memory store with new GTFS-RT delay data.
     * @param delays the list of new delays
     */
    public void update(List<GtfsRtDelay> delays) {
        ArrayListMultimap<String, GtfsRtDelay> tripMap = ArrayListMultimap.create();
        ArrayListMultimap<String, GtfsRtDelay> stopMap = ArrayListMultimap.create();

        for (GtfsRtDelay delay : delays) {
            tripMap.put(delay.tripId(), delay);
            if (delay.parentStopId() != null) {
                stopMap.put(delay.parentStopId(), delay);
            }
        }

        // Atomic update of the maps (replace content)
        delaysByTripId.clear();
        tripMap.asMap().forEach((k, v) -> delaysByTripId.put(k, (List<GtfsRtDelay>) v));

        delaysByStopId.clear();
        stopMap.asMap().forEach((k, v) -> delaysByStopId.put(k, (List<GtfsRtDelay>) v));
    }

    public List<GtfsRtDelay> getDelaysByTripId(String tripId) {
        return delaysByTripId.getOrDefault(tripId, List.of());
    }

    public List<GtfsRtDelay> getDelaysByStopId(String stopId) {
        return delaysByStopId.getOrDefault(stopId, List.of());
    }
}
