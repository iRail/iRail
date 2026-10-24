package be.irail.api.controllers.v1;

import be.irail.api.db.RecentQueryLog;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.test.web.server.LocalServerPort;
import org.springframework.test.context.ActiveProfiles;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertTrue;

/**
 * Proves the request filter is actually registered and fires. The unit tests cover the path
 * mapping in isolation, which would still pass if the filter were never wired into Jersey at
 * all — the failure mode this endpoint would most plausibly ship with.
 */
@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
@ActiveProfiles("test")
class LogsV1EndToEndTest {

    @LocalServerPort
    private int port;

    private final HttpClient httpClient = HttpClient.newHttpClient();

    @BeforeEach
    void reset() {
        RecentQueryLog.getInstance().clear();
    }

    @Test
    void aHandledQueryShowsUpInTheLogsEndpoint() throws Exception {
        get("/v1/stations?format=json", "IntegrationTestAgent/1.0");

        List<Map<String, Object>> logs = RecentQueryLog.getInstance().getRecent().stream()
                .map(entry -> Map.<String, Object>of(
                        "querytype", LogsV1Controller.v1Name(entry.getQueryType()),
                        "user_agent", String.valueOf(entry.getUserAgent())))
                .toList();

        assertFalse(logs.isEmpty(), "the stations query should have been recorded by the filter");
        assertEquals("Stations", logs.getFirst().get("querytype"));
        assertEquals("IntegrationTestAgent/1.0", logs.getFirst().get("user_agent"));
    }

    @Test
    void readingTheLogsDoesNotRecordItself() throws Exception {
        get("/v1/logs", "IntegrationTestAgent/1.0");
        get("/v1/logs", "IntegrationTestAgent/1.0");

        assertTrue(RecentQueryLog.getInstance().getRecent().isEmpty(),
                "requests to the logs endpoint must not appear in the logs");
    }

    private void get(String path, String userAgent) throws Exception {
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create("http://localhost:" + port + path))
                .header("User-Agent", userAgent)
                .GET()
                .build();
        httpClient.send(request, HttpResponse.BodyHandlers.ofString());
    }
}
