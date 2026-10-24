package be.irail.api.db;

import java.time.Duration;
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.Deque;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentLinkedDeque;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * The most recent API queries, kept in memory only.
 *
 * <p>Deliberately not backed by the database. The previous PHP implementation wrote a row per
 * request and was switched off in {@code 08776cef} for "risking the stability of the overall API";
 * at the current peak of roughly 100 requests per second an insert per request would put the same
 * pressure back on a database that also serves station lookups. A bounded in-memory window costs
 * nothing per request, cannot grow without limit, and is exactly what a "recent queries" endpoint
 * needs — nobody reads this to learn what happened last week.
 *
 * <p>Entries are dropped once they fall outside {@link #RETENTION}, and {@link #MAX_ENTRIES} caps
 * memory should traffic spike far beyond the expected peak. Nothing here identifies a caller
 * personally: no IP address is recorded, only the query itself and the User-Agent it arrived with.
 */
public class RecentQueryLog {

    /** How far back the endpoint reports. */
    private static final Duration RETENTION = Duration.ofMinutes(1);

    /**
     * Hard ceiling on retained entries, independent of {@link #RETENTION}. One minute at the
     * expected peak is roughly 6 000 entries, so this leaves headroom without letting an
     * unexpected burst hold an unbounded amount of memory.
     */
    private static final int MAX_ENTRIES = 10_000;

    private static final RecentQueryLog INSTANCE = new RecentQueryLog();

    private final Deque<LogEntry> entries = new ConcurrentLinkedDeque<>();
    private final AtomicInteger sequence = new AtomicInteger();

    private RecentQueryLog() {
    }

    public static RecentQueryLog getInstance() {
        return INSTANCE;
    }

    /**
     * Records one handled query.
     *
     * @param queryType the kind of query that was served
     * @param query     the request parameters
     * @param userAgent the User-Agent the request arrived with, may be null
     */
    public void record(LogQueryType queryType, Map<String, Object> query, String userAgent) {
        record(queryType, query, userAgent, OffsetDateTime.now());
    }

    /**
     * Records one handled query at an explicit time, so ageing out can be exercised in tests.
     *
     * @param queryType the kind of query that was served
     * @param query     the request parameters
     * @param userAgent the User-Agent the request arrived with, may be null
     * @param handledAt when the query was handled
     */
    void record(LogQueryType queryType, Map<String, Object> query, String userAgent, OffsetDateTime handledAt) {
        entries.addLast(new LogEntry(sequence.incrementAndGet(), queryType, query, Map.of(), userAgent, handledAt));
        evictExpired();
    }

    /**
     * The queries handled within the retention window, newest first.
     *
     * @return the retained entries, most recent first
     */
    public List<LogEntry> getRecent() {
        evictExpired();
        List<LogEntry> recent = new ArrayList<>(entries);
        return recent.reversed();
    }

    /** Drops entries that have aged out, then trims to the size ceiling. */
    private void evictExpired() {
        OffsetDateTime cutoff = OffsetDateTime.now().minus(RETENTION);
        // Entries are appended in order, so everything older sits at the head.
        for (LogEntry oldest = entries.peekFirst();
             oldest != null && oldest.getCreatedAt().isBefore(cutoff);
             oldest = entries.peekFirst()) {
            if (!entries.remove(oldest)) {
                break;
            }
        }
        while (entries.size() > MAX_ENTRIES) {
            if (entries.pollFirst() == null) {
                break;
            }
        }
    }

    /** Discards every retained entry. Intended for tests. */
    public void clear() {
        entries.clear();
    }
}
