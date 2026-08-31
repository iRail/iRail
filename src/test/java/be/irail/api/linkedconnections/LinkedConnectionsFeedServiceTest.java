package be.irail.api.linkedconnections;

import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.reader.GtfsReader;
import be.irail.api.gtfs.reader.models.*;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.apache.jena.graph.Node;
import org.apache.jena.graph.NodeFactory;
import org.apache.jena.query.DatasetFactory;
import org.apache.jena.riot.Lang;
import org.apache.jena.riot.RDFParser;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.net.URI;
import java.time.*;
import java.util.List;
import java.util.Map;
import java.util.Set;

import static org.junit.jupiter.api.Assertions.*;

class LinkedConnectionsFeedServiceTest {
    private static final LocalDate SERVICE_DATE = LocalDate.of(2026, 8, 20);
    private static final Instant FIRST_VERSION = Instant.parse("2026-08-20T07:55:00Z");
    private static final String LDES = "https://w3id.org/ldes#";
    private static final String TREE = "https://w3id.org/tree#";
    private static final String DCT = "http://purl.org/dc/terms/";

    private final ObjectMapper objectMapper = new ObjectMapper();
    private GtfsInMemoryDao timetable;
    private LinkedConnectionsEventLog eventLog;
    private LinkedConnectionsFeedService feedService;

    @BeforeEach
    void setUp() {
        Stop brusselsPlatform = stop("S8814001_1", "S8814001", 0);
        Stop ghentPlatform = stop("S8892007_3", "S8892007", 0);
        Stop brussels = stop("S8814001", null, 1);
        Stop ghent = stop("S8892007", null, 1);
        Trip trip = new Trip("trip:123", "route:IC", "weekday", "Oostende", 123, 0, null);
        Route route = new Route("route:IC", "sncb", "IC", "InterCity", null, 2);
        StopTime departure = new StopTime(trip.id(), 35_940, 36_000, brusselsPlatform.id(), 1,
                null, PickupDropoffType.SCHEDULED, PickupDropoffType.SCHEDULED);
        StopTime arrival = new StopTime(trip.id(), 37_800, 37_860, ghentPlatform.id(), 2,
                null, PickupDropoffType.SCHEDULED, PickupDropoffType.SCHEDULED);

        timetable = new GtfsInMemoryDao(new GtfsReader.GtfsData(
                null, List.of(), List.of(new CalendarDate("weekday", SERVICE_DATE)),
                List.of(route), List.of(brusselsPlatform, ghentPlatform, brussels, ghent),
                List.of(departure, arrival), List.of(trip)));
        eventLog = new LinkedConnectionsEventLog(Duration.ofHours(1));
        LinkedConnectionsService connectionsService = new LinkedConnectionsService(objectMapper,
                Duration.ofMinutes(10), ZoneId.of("Europe/Brussels"),
                URI.create("https://creativecommons.org/licenses/by/4.0/"));
        feedService = new LinkedConnectionsFeedService(objectMapper, eventLog, connectionsService,
                URI.create("https://creativecommons.org/licenses/by/4.0/"));
    }

    @Test
    void publishesOnlyChangesAsImmutableVersionedMembers() throws Exception {
        GtfsRtInMemoryDao.Snapshot empty = snapshot(Map.of(), null);
        GtfsRtUpdate delayed = update(60, FIRST_VERSION);
        GtfsRtInMemoryDao.Snapshot first = snapshot(Map.of(delayed.stopId(), delayed), FIRST_VERSION);
        eventLog.record(empty, first, timetable);

        // A new upstream generation with the same observable state is not a change.
        Instant unchangedVersion = FIRST_VERSION.plusSeconds(15);
        GtfsRtUpdate unchanged = update(60, unchangedVersion);
        GtfsRtInMemoryDao.Snapshot second = snapshot(Map.of(unchanged.stopId(), unchanged), unchangedVersion);
        eventLog.record(first, second, timetable);

        String jsonLd = feedService.createFeed(URI.create("https://api.irail.be/1.0/feed"),
                FIRST_VERSION.plusSeconds(30));
        JsonNode json = objectMapper.readTree(jsonLd);

        assertEquals("PT1H", json.at("/ldes:retentionPolicy/fullLogDuration").asText());
        assertEquals(1, json.get("tree:member").size());
        JsonNode member = json.get("tree:member").get(0);
        assertTrue(member.get("@id").asText().contains("?version="));
        assertEquals("2026-08-20T07:55:00.000Z", member.get("modified").asText());
        assertEquals(60, member.get("departureDelay").asInt());
        assertEquals("http://irail.be/connections/trip%3A123/20260820/1",
                member.get("isVersionOf").get("@id").asText());

        var dataset = DatasetFactory.createTxnMem();
        assertDoesNotThrow(() -> RDFParser.fromString(jsonLd).lang(Lang.JSONLD11)
                .parse(dataset.asDatasetGraph()));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(TREE + "member"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(LDES + "fullLogDuration"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(DCT + "isVersionOf"), Node.ANY));
    }

    @Test
    void dropsMembersOutsideTheOneHourWindow() throws Exception {
        GtfsRtUpdate delayed = update(60, FIRST_VERSION);
        eventLog.record(snapshot(Map.of(), null),
                snapshot(Map.of(delayed.stopId(), delayed), FIRST_VERSION), timetable);

        String jsonLd = feedService.createFeed(URI.create("https://api.irail.be/1.0/feed"),
                FIRST_VERSION.plus(Duration.ofHours(1)).plusNanos(1));

        assertTrue(objectMapper.readTree(jsonLd).get("tree:member").isEmpty());
    }

    private GtfsRtUpdate update(int departureDelay, Instant timestamp) {
        return new GtfsRtUpdate(SERVICE_DATE, "trip:123", "S8814001_1", "S8814001",
                0, departureDelay, false, false, OffsetDateTime.ofInstant(timestamp, ZoneOffset.UTC));
    }

    private GtfsRtInMemoryDao.Snapshot snapshot(Map<String, GtfsRtUpdate> updates, Instant version) {
        Map<String, Map<String, GtfsRtUpdate>> byTrip = updates.isEmpty()
                ? Map.of()
                : Map.of("trip:123", updates);
        return new GtfsRtInMemoryDao.Snapshot(byTrip, Map.of(), Set.of(), version);
    }

    private Stop stop(String id, String parent, int locationType) {
        return new Stop(id, null, id, null, 0, 0, null, null, locationType, parent,
                "Europe/Brussels", 0, null, null, Map.of());
    }
}
