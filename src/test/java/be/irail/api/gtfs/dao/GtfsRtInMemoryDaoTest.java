package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertSame;

class GtfsRtInMemoryDaoTest {

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

    private GtfsRtUpdate update(String stopId, String parentStopId) {
        return new GtfsRtUpdate(LocalDate.of(2026, 8, 14), "trip", stopId, parentStopId,
                0, 0, false, false, null);
    }
}
