package be.irail.api.linkedconnections;

import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.dao.models.GtfsConnection;
import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.time.Duration;
import java.time.Instant;
import java.time.LocalDate;
import java.util.*;

/**
 * In-memory, rolling change log used by the one-page Linked Connections LDES.
 * Only connection states whose observable realtime fields changed are retained.
 */
@Service
public class LinkedConnectionsEventLog {
    private final Duration retention;
    private final Deque<ConnectionVersion> versions = new ArrayDeque<>();

    public LinkedConnectionsEventLog(
            @Value("${linked-connections.feed-retention:PT1H}") Duration retention) {
        if (retention.isZero() || retention.isNegative()) {
            throw new IllegalArgumentException("Linked Connections feed retention must be positive");
        }
        this.retention = retention;
    }

    public Duration getRetention() {
        return retention;
    }

    /** Records the connection-level differences between two complete GTFS-RT generations. */
    public synchronized void record(GtfsRtInMemoryDao.Snapshot previous,
                                    GtfsRtInMemoryDao.Snapshot current,
                                    GtfsInMemoryDao timetable) {
        if (current.version() == null || timetable == null) {
            return;
        }

        Set<DatedTripId> candidates = datedTrips(previous);
        candidates.addAll(datedTrips(current));

        for (DatedTripId trip : candidates) {
            for (GtfsConnection connection : timetable.getConnectionsForTrip(trip.tripId(), trip.date())) {
                RealtimeState before = state(connection, previous);
                RealtimeState after = state(connection, current);
                if (!before.equals(after)) {
                    versions.addLast(new ConnectionVersion(connection, current.version(), after));
                }
            }
        }

        Instant cutoff = current.version().minus(retention);
        versions.removeIf(version -> version.timestamp().isBefore(cutoff));
    }

    /** Returns a stable chronological copy of the retained versions. */
    public synchronized List<ConnectionVersion> versionsSince(Instant cutoff) {
        return versions.stream()
                .filter(version -> !version.timestamp().isBefore(cutoff))
                .sorted(Comparator.comparing(ConnectionVersion::timestamp)
                        .thenComparing(version -> connectionKey(version.connection())))
                .toList();
    }

    private static Set<DatedTripId> datedTrips(GtfsRtInMemoryDao.Snapshot snapshot) {
        Set<DatedTripId> trips = new HashSet<>(snapshot.canceledTrips());
        snapshot.updatesByTripIdAndStop().forEach((tripId, updates) -> updates.values().stream()
                .map(GtfsRtUpdate::startDate)
                .filter(Objects::nonNull)
                .forEach(date -> trips.add(new DatedTripId(tripId, date))));
        return trips;
    }

    private static RealtimeState state(GtfsConnection connection, GtfsRtInMemoryDao.Snapshot snapshot) {
        GtfsRtUpdate departure = matchingUpdate(snapshot, connection, connection.departureCall().stopId());
        GtfsRtUpdate arrival = matchingUpdate(snapshot, connection, connection.arrivalCall().stopId());
        boolean cancelled = snapshot.isCanceled(connection.trip().id(), connection.tripStartDate())
                || departure != null && departure.cancelled()
                || arrival != null && arrival.cancelled();
        return new RealtimeState(
                departure == null ? 0 : departure.departureDelay(),
                arrival == null ? 0 : arrival.arrivalDelay(),
                cancelled);
    }

    private static GtfsRtUpdate matchingUpdate(GtfsRtInMemoryDao.Snapshot snapshot,
                                               GtfsConnection connection,
                                               String stopId) {
        GtfsRtUpdate update = snapshot.getUpdatesByTripId(connection.trip().id()).get(stopId);
        return update != null && connection.tripStartDate().equals(update.startDate()) ? update : null;
    }

    private static String connectionKey(GtfsConnection connection) {
        return connection.trip().id() + ":" + connection.tripStartDate() + ":"
                + connection.departureCall().stopSequence();
    }

    public record ConnectionVersion(GtfsConnection connection, Instant timestamp, RealtimeState state) {
    }

    public record RealtimeState(int departureDelay, int arrivalDelay, boolean cancelled) {
    }
}
