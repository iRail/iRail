package be.irail.api.linkedconnections;

import be.irail.api.exception.InternalProcessingException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.dao.GtfsRtInMemoryDao;
import be.irail.api.gtfs.dao.models.GtfsConnection;
import be.irail.api.gtfs.reader.models.GtfsRtUpdate;
import be.irail.api.gtfs.reader.models.PickupDropoffType;
import be.irail.api.gtfs.reader.models.Stop;
import be.irail.api.util.IrailUri;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.net.URI;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.*;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeFormatterBuilder;
import java.util.*;

/** Builds Linked Connections 1.0 JSON-LD fragments from the in-memory GTFS feed. */
@Service
public class LinkedConnectionsService {
    public static final String JSON_LD_MEDIA_TYPE = "application/ld+json";
    private static final String IRAIL_STATION_BASE = "http://irail.be/stations/NMBS/";
    private static final String IRAIL_VEHICLE_BASE = "http://irail.be/vehicle/";
    private static final Duration REALTIME_LOOKAROUND = Duration.ofHours(24);
    private static final DateTimeFormatter INSTANT_FORMATTER = new DateTimeFormatterBuilder().appendInstant(3).toFormatter();
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.BASIC_ISO_DATE;

    private final ObjectMapper objectMapper;
    private final Duration pageDuration;
    private final ZoneId timetableZone;
    private final URI licenseUri;

    public LinkedConnectionsService(
            ObjectMapper objectMapper,
            @Value("${linked-connections.page-duration:PT10M}") Duration pageDuration,
            @Value("${linked-connections.time-zone:Europe/Brussels}") ZoneId timetableZone,
            @Value("${linked-connections.license:https://creativecommons.org/licenses/by/4.0/}") URI licenseUri) {
        if (pageDuration.isZero() || pageDuration.isNegative()) {
            throw new IllegalArgumentException("Linked Connections page duration must be positive");
        }
        this.objectMapper = objectMapper;
        this.pageDuration = pageDuration;
        this.timetableZone = timetableZone;
        this.licenseUri = licenseUri;
    }

    public Duration getPageDuration() {
        return pageDuration;
    }

    public Instant pageStart(Instant requestedTime) {
        long pageSeconds = pageDuration.toSeconds();
        if (pageDuration.getNano() != 0 || pageSeconds < 1) {
            throw new IllegalStateException("Linked Connections page duration must be a whole number of seconds");
        }
        return Instant.ofEpochSecond(Math.floorDiv(requestedTime.getEpochSecond(), pageSeconds) * pageSeconds);
    }

    public String formatInstant(Instant instant) {
        return INSTANT_FORMATTER.format(instant);
    }

    public String createPage(URI graphUri, Instant start) {
        GtfsInMemoryDao staticDao = GtfsInMemoryDao.getInstance();
        if (staticDao == null) {
            throw new InternalProcessingException("GTFS timetable data is not available");
        }

        Instant end = start.plus(pageDuration);
        LocalDateTime candidateStart = LocalDateTime.ofInstant(start.minus(REALTIME_LOOKAROUND), timetableZone);
        LocalDateTime candidateEnd = LocalDateTime.ofInstant(end.plus(REALTIME_LOOKAROUND), timetableZone);
        GtfsRtInMemoryDao.Snapshot realtimeSnapshot = GtfsRtInMemoryDao.getInstance().getSnapshot();

        List<RenderedConnection> connections = staticDao.getConnections(candidateStart, candidateEnd).stream()
            .map(connection -> render(connection, realtimeSnapshot))
                .filter(connection -> !connection.departureTime().isBefore(start) && connection.departureTime().isBefore(end))
                .sorted(Comparator.comparing(RenderedConnection::departureTime).thenComparing(RenderedConnection::id))
                .toList();

        Map<String, Object> page = new LinkedHashMap<>();
        page.put("@context", context());
        page.put("@id", pageUri(graphUri, start));
        page.put("@type", "hydra:PartialCollectionView");
        page.put("hydra:previous", id(pageUri(graphUri, start.minus(pageDuration))));
        page.put("hydra:next", id(pageUri(graphUri, end)));
        page.put("hydra:search", searchControl(graphUri));
        page.put("dct:license", id(licenseUri.toString()));
        page.put("gtfsRtVersion", realtimeSnapshot.version() == null
            ? null
            : formatInstant(realtimeSnapshot.version()));
        page.put("@graph", connections.stream().map(RenderedConnection::json).toList());

        try {
            return objectMapper.writeValueAsString(page);
        } catch (JsonProcessingException exception) {
            throw new InternalProcessingException("Failed to serialize the Linked Connections graph", exception);
        }
    }

    private RenderedConnection render(GtfsConnection connection, GtfsRtInMemoryDao.Snapshot realtimeSnapshot) {
        GtfsRtUpdate departureUpdate = matchingUpdate(realtimeSnapshot, connection, connection.departureCall().stopId());
        GtfsRtUpdate arrivalUpdate = matchingUpdate(realtimeSnapshot, connection, connection.arrivalCall().stopId());
        int departureDelay = departureUpdate == null ? 0 : departureUpdate.departureDelay();
        int arrivalDelay = arrivalUpdate == null ? 0 : arrivalUpdate.arrivalDelay();
        boolean cancelled = realtimeSnapshot.isCanceled(connection.trip().id(), connection.tripStartDate())
                || departureUpdate != null && departureUpdate.cancelled()
                || arrivalUpdate != null && arrivalUpdate.cancelled();

        return render(connection, departureDelay, arrivalDelay, cancelled);
    }

    private RenderedConnection render(GtfsConnection connection, int departureDelay, int arrivalDelay,
                                      boolean cancelled) {
        Instant departureTime = connection.departureCall().getDepartureTime(connection.tripStartDate())
                .atZone(timetableZone).toInstant().plusSeconds(departureDelay);
        Instant arrivalTime = connection.arrivalCall().getArrivalTime(connection.tripStartDate())
                .atZone(timetableZone).toInstant().plusSeconds(arrivalDelay);
        String connectionId = connectionUri(connection);

        Map<String, Object> json = new LinkedHashMap<>();
        json.put("@id", connectionId);
        json.put("@type", cancelled ? "CancelledConnection" : "Connection");
        json.put("departureStop", stationUri(connection.departureStop()));
        json.put("arrivalStop", stationUri(connection.arrivalStop()));
        json.put("departureTime", formatInstant(departureTime));
        json.put("arrivalTime", formatInstant(arrivalTime));
        json.put("departureDelay", departureDelay);
        json.put("arrivalDelay", arrivalDelay);
        json.put("gtfs:trip", tripUri(connection));
        json.put("gtfs:route", routeUri(connection));
        if (connection.trip().headsign() != null) {
            json.put("gtfs:headsign", connection.trip().headsign());
        }
        json.put("gtfs:pickupType", pickupDropoffUri(cancelled ? PickupDropoffType.NONE : connection.departureCall().pickupType()));
        json.put("gtfs:dropOffType", pickupDropoffUri(cancelled ? PickupDropoffType.NONE : connection.arrivalCall().dropOffType()));
        return new RenderedConnection(connectionId, departureTime, json);
    }

    Map<String, Object> renderVersion(LinkedConnectionsEventLog.ConnectionVersion version) {
        LinkedConnectionsEventLog.RealtimeState state = version.state();
        RenderedConnection rendered = render(version.connection(), state.departureDelay(), state.arrivalDelay(),
                state.cancelled());
        Map<String, Object> member = new LinkedHashMap<>(rendered.json());
        member.put("@id", rendered.id() + "?version=" + pathSegment(formatInstant(version.timestamp())));
        member.put("isVersionOf", id(rendered.id()));
        member.put("modified", formatInstant(version.timestamp()));
        return member;
    }

    private GtfsRtUpdate matchingUpdate(GtfsRtInMemoryDao.Snapshot realtimeSnapshot, GtfsConnection connection, String stopId) {
        GtfsRtUpdate update = realtimeSnapshot.getUpdatesByTripId(connection.trip().id()).get(stopId);
        return update != null && connection.tripStartDate().equals(update.startDate()) ? update : null;
    }

    private Map<String, Object> context() {
        Map<String, Object> context = new LinkedHashMap<>();
        context.put("lc", "http://semweb.mmlab.be/ns/linkedconnections#");
        context.put("gtfs", "http://vocab.gtfs.org/terms#");
        context.put("hydra", "http://www.w3.org/ns/hydra/core#");
        context.put("dct", "http://purl.org/dc/terms/");
        context.put("xsd", "http://www.w3.org/2001/XMLSchema#");
        context.put("Connection", "lc:Connection");
        context.put("CancelledConnection", "lc:CancelledConnection");
        context.put("gtfsRtVersion", typedTerm("http://irail.be/ns/gtfsRtVersion", "xsd:dateTime"));
        context.put("departureStop", iriTerm("lc:departureStop"));
        context.put("arrivalStop", iriTerm("lc:arrivalStop"));
        context.put("departureTime", typedTerm("lc:departureTime", "xsd:dateTime"));
        context.put("arrivalTime", typedTerm("lc:arrivalTime", "xsd:dateTime"));
        context.put("departureDelay", typedTerm("lc:departureDelay", "xsd:integer"));
        context.put("arrivalDelay", typedTerm("lc:arrivalDelay", "xsd:integer"));
        context.put("nextConnection", iriTerm("lc:nextConnection"));
        context.put("gtfs:trip", Map.of("@type", "@id"));
        context.put("gtfs:route", Map.of("@type", "@id"));
        context.put("gtfs:pickupType", Map.of("@type", "@id"));
        context.put("gtfs:dropOffType", Map.of("@type", "@id"));
        return context;
    }

    private Map<String, Object> searchControl(URI graphUri) {
        Map<String, Object> mapping = new LinkedHashMap<>();
        mapping.put("@type", "hydra:IriTemplateMapping");
        mapping.put("hydra:variable", "departureTime");
        mapping.put("hydra:required", true);
        mapping.put("hydra:property", id("http://semweb.mmlab.be/ns/linkedconnections#departureTimeQuery"));

        Map<String, Object> search = new LinkedHashMap<>();
        search.put("@type", "hydra:IriTemplate");
        search.put("hydra:template", graphUri + "{?departureTime}");
        search.put("hydra:variableRepresentation", id("http://www.w3.org/ns/hydra/core#BasicRepresentation"));
        search.put("hydra:mapping", mapping);
        return search;
    }

    private String pageUri(URI graphUri, Instant start) {
        return graphUri + "?departureTime=" + formatInstant(start);
    }

    private String connectionUri(GtfsConnection connection) {
        String hafasId = connection.departureStop().getHafasId();
        String stationId = hafasId == null ? connection.departureStop().id() : hafasId;
        LocalDate departureDate = connection.departureCall().getDepartureTime(connection.tripStartDate()).toLocalDate();
        return IrailUri.connection(stationId, departureDate, vehicleId(connection));
    }

    private String tripUri(GtfsConnection connection) {
        return IRAIL_VEHICLE_BASE + pathSegment(vehicleId(connection)) + "/"
                + DATE_FORMATTER.format(connection.tripStartDate());
    }

    private String routeUri(GtfsConnection connection) {
        return IRAIL_VEHICLE_BASE + pathSegment(vehicleId(connection));
    }

    private String vehicleId(GtfsConnection connection) {
        String label = connection.route() == null ? "" : Objects.toString(connection.route().shortName(), "");
        return label + connection.trip().shortName();
    }

    private String stationUri(Stop stop) {
        String hafasId = stop.getHafasId();
        return IRAIL_STATION_BASE + (hafasId == null ? pathSegment(stop.id()) : "00" + hafasId);
    }

    private String pickupDropoffUri(PickupDropoffType type) {
        if (type == null) {
            return "gtfs:NotAvailable";
        }
        return switch (type) {
            case SCHEDULED -> "gtfs:Regular";
            case NONE -> "gtfs:NotAvailable";
            case PHONE_TO_ARRANGE -> "gtfs:MustPhone";
            case COORDINATE_WITH_DRIVER -> "gtfs:MustCoordinateWithDriver";
        };
    }

    private String pathSegment(String value) {
        return URLEncoder.encode(value, StandardCharsets.UTF_8).replace("+", "%20");
    }

    private Map<String, String> id(String value) {
        return Map.of("@id", value);
    }

    private Map<String, String> iriTerm(String id) {
        return Map.of("@id", id, "@type", "@id");
    }

    private Map<String, String> typedTerm(String id, String type) {
        return Map.of("@id", id, "@type", type);
    }

    private record RenderedConnection(String id, Instant departureTime, Map<String, Object> json) {
    }
}
