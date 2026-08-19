package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.DatedTripId;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import org.junit.jupiter.api.Test;

import java.time.Duration;
import java.time.Instant;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.OffsetDateTime;
import java.time.ZoneId;
import java.util.List;
import java.util.Set;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertSame;
import static org.junit.jupiter.api.Assertions.assertTrue;

class GtfsRtInMemoryDaoTest {

    private static final LocalDateTime NOW = LocalDateTime.of(2026, 8, 19, 12, 0);

    @Test
    void indexesNamespacedParentByHafasId() {
        GtfsRtUpdate update = update("gs:nmbssncb:8811130_3", "gs:nmbssncb:S8811130");

        GtfsRtInMemoryDao dao = GtfsRtInMemoryDao.getInstance();
        dao.updateStopTimeUpdates(List.of(update));

        assertSame(update, dao.getUpdatesByHafasStopId("8811130").get("trip"));
    }

    @Test
    void fallsBackToNumericPlatformForNameBasedInternationalParent() {
        GtfsRtUpdate update = update("gs:nmbssncb:7015400", "nmbssncb:s-londonstpancrasgb");

        GtfsRtInMemoryDao dao = GtfsRtInMemoryDao.getInstance();
        dao.updateStopTimeUpdates(List.of(update));

        assertSame(update, dao.getUpdatesByHafasStopId("7015400").get("trip"));
    }

    @Test
    void freshFeedAppliesEvenForDistantDeparture() {
        OffsetDateTime freshFeed = NOW.minusMinutes(1).atZone(ZoneId.systemDefault()).toOffsetDateTime();
        assertTrue(GtfsRtInMemoryDao.isOverlayUsable(freshFeed, NOW.plusHours(5), NOW));
    }

    @Test
    void staleFeedKeepsImminentDeparture() {
        OffsetDateTime staleFeed = NOW.minusMinutes(30).atZone(ZoneId.systemDefault()).toOffsetDateTime();
        assertTrue(GtfsRtInMemoryDao.isOverlayUsable(staleFeed, NOW.plusMinutes(10), NOW));
    }

    @Test
    void staleFeedDropsDistantDeparture() {
        OffsetDateTime staleFeed = NOW.minusMinutes(30).atZone(ZoneId.systemDefault()).toOffsetDateTime();
        assertFalse(GtfsRtInMemoryDao.isOverlayUsable(staleFeed, NOW.plusHours(3), NOW));
    }

    @Test
    void missingFeedTimestampFallsBackToKeepWindow() {
        assertTrue(GtfsRtInMemoryDao.isOverlayUsable(null, NOW.plusMinutes(10), NOW));
        assertFalse(GtfsRtInMemoryDao.isOverlayUsable(null, NOW.plusHours(3), NOW));
    }

    @Test
    void cancellationsAreServedWhileFresh() {
        LocalDate date = LocalDate.of(2026, 8, 19);
        GtfsRtInMemoryDao dao = GtfsRtInMemoryDao.getInstance();
        dao.updateCanceledTrips(Set.of(new DatedTripId("trip", date)));
        dao.setFeedTimestamp(Instant.now());

        assertTrue(dao.isCanceled("trip", date));
    }

    @Test
    void cancellationsAgeOutWhenStale() {
        LocalDate date = LocalDate.of(2026, 8, 19);
        GtfsRtInMemoryDao dao = GtfsRtInMemoryDao.getInstance();
        dao.updateCanceledTrips(Set.of(new DatedTripId("trip", date)));
        dao.setFeedTimestamp(Instant.now().minus(Duration.ofHours(1)));

        assertFalse(dao.isCanceled("trip", date));
    }

    private GtfsRtUpdate update(String stopId, String parentStopId) {
        return new GtfsRtUpdate(LocalDate.of(2026, 8, 14), "trip", stopId, parentStopId,
                0, 0, false, false, null);
    }
}
