package be.irail.api.gtfs.reader;

import com.google.transit.realtime.GtfsRealtime.FeedHeader;
import com.google.transit.realtime.GtfsRealtime.FeedMessage;
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpServer;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.test.util.ReflectionTestUtils;

import java.io.IOException;
import java.net.InetSocketAddress;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertNull;

/**
 * Exercises the fetch behaviour this reader needs for an authenticated, quota-limited feed:
 * sending the key, and backing off when the gateway refuses. Served by a local HTTP server so no
 * request leaves the machine.
 */
class GtfsRtReaderTest {

    private HttpServer server;
    private final List<String> receivedKeys = new CopyOnWriteArrayList<>();
    private int responseStatus = 200;
    private String retryAfter;

    @BeforeEach
    void startServer() throws IOException {
        receivedKeys.clear();
        responseStatus = 200;
        retryAfter = null;
        server = HttpServer.create(new InetSocketAddress("127.0.0.1", 0), 0);
        server.createContext("/feed", this::handle);
        server.start();
    }

    @AfterEach
    void stopServer() {
        server.stop(0);
    }

    private void handle(HttpExchange exchange) throws IOException {
        receivedKeys.add(String.valueOf(exchange.getRequestHeaders().getFirst("bmc-partner-key")));
        if (retryAfter != null) {
            exchange.getResponseHeaders().add("Retry-After", retryAfter);
        }
        byte[] body = responseStatus == 200 ? feed() : "nope".getBytes();
        exchange.sendResponseHeaders(responseStatus, body.length);
        exchange.getResponseBody().write(body);
        exchange.close();
    }

    private static byte[] feed() {
        return FeedMessage.newBuilder()
                .setHeader(FeedHeader.newBuilder().setGtfsRealtimeVersion("2.0").setTimestamp(1_787_000_000L))
                .build()
                .toByteArray();
    }

    private GtfsRtReader reader(String apiKey) {
        GtfsRtReader reader = new GtfsRtReader();
        ReflectionTestUtils.setField(reader, "gtfsRtUrl",
                "http://127.0.0.1:" + server.getAddress().getPort() + "/feed");
        ReflectionTestUtils.setField(reader, "apiKey", apiKey);
        ReflectionTestUtils.setField(reader, "apiKeyHeader", "bmc-partner-key");
        return reader;
    }

    @Test
    void sendsTheConfiguredKeyAndParsesTheFeed() {
        FeedMessage feed = reader("a-partner-key").readTripUpdates();

        assertNotNull(feed, "a 200 with valid protobuf should parse");
        assertEquals("2.0", feed.getHeader().getGtfsRealtimeVersion());
        assertEquals(List.of("a-partner-key"), receivedKeys);
    }

    @Test
    void omitsTheHeaderWhenNoKeyIsConfigured() {
        assertNotNull(reader("").readTripUpdates());

        assertEquals(List.of("null"), receivedKeys, "no key configured means no key header");
    }

    @Test
    void backsOffAfterAQuotaRefusalInsteadOfRetryingImmediately() {
        responseStatus = 403;
        retryAfter = "120";
        GtfsRtReader reader = reader("a-partner-key");

        assertNull(reader.readTripUpdates(), "a refused fetch yields no feed");
        assertNull(reader.readTripUpdates(), "still refused while in cooldown");

        assertEquals(1, receivedKeys.size(), "the second poll must not reach the gateway");
    }

    @Test
    void backsOffOnRateLimitingToo() {
        responseStatus = 429;
        GtfsRtReader reader = reader("a-partner-key");

        assertNull(reader.readTripUpdates());
        assertNull(reader.readTripUpdates());

        assertEquals(1, receivedKeys.size());
    }

    @Test
    void toleratesARetryAfterThatIsNotANumber() {
        responseStatus = 429;
        retryAfter = "Wed, 19 Aug 2026 12:00:00 GMT";
        GtfsRtReader reader = reader("a-partner-key");

        assertNull(reader.readTripUpdates());
        assertNull(reader.readTripUpdates(), "an HTTP-date falls back to the default cooldown");

        assertEquals(1, receivedKeys.size());
    }

    @Test
    void backsOffWhenTheKeyIsRejected() {
        // A 401 is a key the gateway will not accept. Retrying it every 30s cannot succeed,
        // and against a gateway that counts refusals it is the wrong thing to do.
        responseStatus = 401;
        GtfsRtReader reader = reader("a-stale-key");

        assertNull(reader.readTripUpdates());
        assertNull(reader.readTripUpdates());

        assertEquals(1, receivedKeys.size(), "the second poll must not reach the gateway");
    }

    @Test
    void keepsPollingAfterAnUnexpectedStatus() {
        // A 500 is a transient upstream fault, not a quota decision, so it must not silence the
        // poller the way a 403 does.
        responseStatus = 500;
        GtfsRtReader reader = reader("a-partner-key");

        assertNull(reader.readTripUpdates());
        assertNull(reader.readTripUpdates());

        assertEquals(2, receivedKeys.size(), "an unexpected status should not start a cooldown");
    }
}
