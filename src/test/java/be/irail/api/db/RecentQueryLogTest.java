package be.irail.api.db;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.time.OffsetDateTime;
import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class RecentQueryLogTest {

    private final RecentQueryLog log = RecentQueryLog.getInstance();

    @BeforeEach
    void reset() {
        log.clear();
    }

    @Test
    void reportsNewestFirst() {
        log.record(LogQueryType.LIVEBOARD, Map.of("station", "Brussel-Zuid"), "first");
        log.record(LogQueryType.STATIONS, Map.of(), "second");

        List<LogEntry> recent = log.getRecent();

        assertEquals(2, recent.size());
        assertEquals("second", recent.getFirst().getUserAgent());
        assertEquals("first", recent.getLast().getUserAgent());
    }

    @Test
    void keepsTheQueryAndUserAgentItWasGiven() {
        log.record(LogQueryType.JOURNEYPLANNING, Map.of("from", "Gent", "to", "Brugge"), "BeTrains/1.0");

        LogEntry entry = log.getRecent().getFirst();

        assertEquals(LogQueryType.JOURNEYPLANNING, entry.getQueryType());
        assertEquals(Map.of("from", "Gent", "to", "Brugge"), entry.getQuery());
        assertEquals("BeTrains/1.0", entry.getUserAgent());
    }

    @Test
    void agesOutEntriesOlderThanTheRetentionWindow() {
        log.record(LogQueryType.LIVEBOARD, Map.of(), "stale", OffsetDateTime.now().minusMinutes(5));
        log.record(LogQueryType.LIVEBOARD, Map.of(), "fresh");

        List<LogEntry> recent = log.getRecent();

        assertEquals(1, recent.size(), "the five-minute-old entry should have aged out");
        assertEquals("fresh", recent.getFirst().getUserAgent());
    }

    @Test
    void toleratesANullUserAgent() {
        log.record(LogQueryType.STATIONS, Map.of(), null);

        assertEquals(1, log.getRecent().size());
    }

    @Test
    void staysBoundedUnderABurstFarBeyondTheExpectedPeak() {
        // A minute at ~100 req/s is roughly 6 000 entries; this pushes well past that to confirm the
        // ceiling holds, since the whole point of keeping this in memory is that it cannot run away.
        for (int i = 0; i < 25_000; i++) {
            log.record(LogQueryType.LIVEBOARD, Map.of("station", "Brussel-Zuid"), "burst");
        }

        assertTrue(log.getRecent().size() <= 10_000, "retained entries must stay under the ceiling");
    }
}
