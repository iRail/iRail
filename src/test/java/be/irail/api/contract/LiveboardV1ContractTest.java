package be.irail.api.contract;

import be.irail.api.db.StationsDao;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.reader.GtfsReader;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ObjectNode;
import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.context.annotation.Import;
import org.springframework.boot.test.web.server.LocalServerPort;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.context.jdbc.Sql;
import org.springframework.test.util.ReflectionTestUtils;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;

/**
 * Pins the V1 liveboard contract: a recorded NMBS response must always produce the same bytes.
 *
 * <p>The request goes over real HTTP to the running application, so routing, conversion and
 * serialisation are all exercised — only the call to NMBS is replaced, by
 * {@link CassetteRivConfiguration}. No request leaves the machine, so this runs on every commit
 * without a key and without adding load to NMBS.
 *
 * <p>When this test fails, either the change was intended — re-record and review the diff — or the
 * V1 output has silently drifted, which is the whole point of pinning it.
 */
@SpringBootTest(webEnvironment = SpringBootTest.WebEnvironment.RANDOM_PORT)
@ActiveProfiles("test")
@Import(CassetteRivConfiguration.class)
@Sql(scripts = "/sql/liveboard-cassette-stations.sql")
class LiveboardV1ContractTest {

    /**
     * Only the root timestamp is wall-clock dependent; everything else derives from the cassette.
     * It is blanked rather than ignored, so a field moving into or out of the response still fails.
     */
    private static final List<String> VOLATILE_ROOT_FIELDS = List.of("timestamp");

    private static final ObjectMapper MAPPER = new ObjectMapper();

    @LocalServerPort
    private int port;

    @Autowired
    private StationsDao stationsDao;

    private final HttpClient httpClient = HttpClient.newHttpClient();

    @BeforeAll
    static void emptyGtfsIndex() {
        // The liveboard path asks GTFS for a journey start date and falls back to the planned date
        // when it finds none. An empty index makes that fallback deterministic; without an instance
        // at all the lookup would throw.
        GtfsInMemoryDao.setInstance(new GtfsInMemoryDao(new GtfsReader.GtfsData(
                null, List.of(), List.of(), List.of(), List.of(), List.of(), List.of())));
    }

    @BeforeEach
    void loadFixtureStations() {
        // StationsDao caches the station list on first use, which happens before @Sql runs.
        // Deliberately no @Transactional on this class: the request is served on another thread,
        // so the fixture has to be committed to be visible at all.
        // Package-private, and initializeStations() is a no-op once the cache is populated.
        ReflectionTestUtils.invokeMethod(stationsDao, "updateStationsFromDatabase");
    }

    @Test
    void liveboardMatchesTheRecordedContract() throws Exception {
        String actual = get("/v1/liveboard?id=BE.NMBS.008814001&format=json&lang=en");

        if (Boolean.getBoolean("contract.record")) {
            record("liveboard-brussels-south.json", actual);
            return;
        }
        assertEquals(normalise(Cassettes.golden("liveboard-brussels-south.json")), normalise(actual));
    }

    /**
     * Rewrites a golden file from the current output. Run deliberately, with
     * {@code -Dcontract.record=true}, and review the resulting diff — never to make a red test green.
     */
    private static void record(String name, String body) throws IOException {
        Path target = Path.of("src/test/resources/golden", name);
        Files.createDirectories(target.getParent());
        Files.writeString(target, MAPPER.writerWithDefaultPrettyPrinter()
                .writeValueAsString(MAPPER.readTree(body)));
    }

    /** Blanks the wall-clock fields so the comparison is stable, keeping every other byte significant. */
    private static String normalise(String json) throws Exception {
        ObjectNode root = (ObjectNode) MAPPER.readTree(json);
        VOLATILE_ROOT_FIELDS.forEach(field -> root.put(field, "<volatile>"));
        return MAPPER.writerWithDefaultPrettyPrinter().writeValueAsString(root);
    }

    private String get(String path) throws Exception {
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create("http://localhost:" + port + path))
                .GET()
                .build();
        HttpResponse<String> response = httpClient.send(request, HttpResponse.BodyHandlers.ofString());
        assertEquals(200, response.statusCode(), "expected a successful liveboard response, got: " + response.body());
        return response.body();
    }
}
