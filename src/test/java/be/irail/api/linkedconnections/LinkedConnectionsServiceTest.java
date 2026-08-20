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
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.net.URI;
import java.time.*;
import java.util.List;
import java.util.Map;
import java.util.Set;

import static org.junit.jupiter.api.Assertions.*;

class LinkedConnectionsServiceTest {
    private static final LocalDate SERVICE_DATE = LocalDate.of(2026, 8, 20);
    private static final Instant PAGE_START = Instant.parse("2026-08-20T08:00:00Z");
    private static final String LC = "http://semweb.mmlab.be/ns/linkedconnections#";
    private static final String HYDRA = "http://www.w3.org/ns/hydra/core#";
        private static final String GTFS = "http://vocab.gtfs.org/terms#";

    private final ObjectMapper objectMapper = new ObjectMapper();
    private LinkedConnectionsService service;

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

        GtfsReader.GtfsData data = new GtfsReader.GtfsData(
                null, List.of(), List.of(new CalendarDate("weekday", SERVICE_DATE)),
                List.of(route), List.of(brusselsPlatform, ghentPlatform, brussels, ghent),
                List.of(departure, arrival), List.of(trip));
        GtfsInMemoryDao.setInstance(new GtfsInMemoryDao(data));
        GtfsRtInMemoryDao.getInstance().updateCanceledTrips(Set.of());
        GtfsRtInMemoryDao.getInstance().updateStopTimeUpdates(List.of(new GtfsRtUpdate(
                SERVICE_DATE, trip.id(), departure.stopId(), brussels.id(), 0, 60,
                false, false, OffsetDateTime.parse("2026-08-20T07:55:00Z"))));

        service = new LinkedConnectionsService(objectMapper, Duration.ofMinutes(10),
                ZoneId.of("Europe/Brussels"), URI.create("https://creativecommons.org/licenses/by/4.0/"));
    }

    @AfterEach
    void tearDown() {
        GtfsRtInMemoryDao.getInstance().updateStopTimeUpdates(List.of());
        GtfsRtInMemoryDao.getInstance().updateCanceledTrips(Set.of());
    }

    @Test
    void createsValidRdfWithConnectionsAndHypermediaControls() throws Exception {
        String jsonLd = service.createPage(URI.create("https://api.irail.be/graph"), PAGE_START);
        JsonNode json = objectMapper.readTree(jsonLd);

        assertEquals("https://api.irail.be/graph?departureTime=2026-08-20T08:00:00.000Z", json.get("@id").asText());
        assertEquals("2026-08-20T08:01:00.000Z", json.get("@graph").get(0).get("departureTime").asText());
        assertEquals(60, json.get("@graph").get(0).get("departureDelay").asInt());
        assertEquals("http://irail.be/stations/NMBS/008814001", json.get("@graph").get(0).get("departureStop").asText());

        var dataset = DatasetFactory.createTxnMem();
        assertDoesNotThrow(() -> RDFParser.fromString(jsonLd).lang(Lang.JSONLD11).parse(dataset.asDatasetGraph()));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(LC + "departureStop"),
                NodeFactory.createURI("http://irail.be/stations/NMBS/008814001")));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(LC + "departureTime"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(LC + "arrivalStop"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(LC + "arrivalTime"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(GTFS + "trip"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(HYDRA + "previous"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(HYDRA + "next"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI(HYDRA + "search"), Node.ANY));
        assertTrue(dataset.asDatasetGraph().contains(Node.ANY, Node.ANY,
                NodeFactory.createURI("http://purl.org/dc/terms/license"), Node.ANY));
    }

    @Test
    void pageStartUsesStableTenMinuteUtcBoundaries() {
        assertEquals(PAGE_START, service.pageStart(Instant.parse("2026-08-20T08:09:59Z")));
        assertEquals(Instant.parse("2026-08-20T07:50:00Z"), PAGE_START.minus(service.getPageDuration()));
    }

    private Stop stop(String id, String parent, int locationType) {
        return new Stop(id, null, id, null, 0, 0, null, null, locationType, parent,
                "Europe/Brussels", 0, null, null, Map.of());
    }
}