package be.irail.api.linkedconnections;

import be.irail.api.exception.InternalProcessingException;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.net.URI;
import java.time.Instant;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/** Builds the single-page, rolling Linked Connections LDES. */
@Service
public class LinkedConnectionsFeedService {
    private final ObjectMapper objectMapper;
    private final LinkedConnectionsEventLog eventLog;
    private final LinkedConnectionsService connectionsService;
    private final URI licenseUri;

    public LinkedConnectionsFeedService(
            ObjectMapper objectMapper,
            LinkedConnectionsEventLog eventLog,
            LinkedConnectionsService connectionsService,
            @Value("${linked-connections.license:https://creativecommons.org/licenses/by/4.0/}") URI licenseUri) {
        this.objectMapper = objectMapper;
        this.eventLog = eventLog;
        this.connectionsService = connectionsService;
        this.licenseUri = licenseUri;
    }

    public String createFeed(URI feedUri, Instant now) {
        List<Map<String, Object>> members = eventLog.versionsSince(now.minus(eventLog.getRetention())).stream()
                .map(connectionsService::renderVersion)
                .toList();

        Map<String, Object> feed = new LinkedHashMap<>();
        feed.put("@context", context());
        feed.put("@id", feedUri.toString());
        feed.put("@type", List.of("ldes:EventStream", "tree:Node"));
        feed.put("tree:view", id(feedUri.toString()));
        feed.put("ldes:timestampPath", id("http://purl.org/dc/terms/modified"));
        feed.put("ldes:versionOfPath", id("http://purl.org/dc/terms/isVersionOf"));
        feed.put("pollingInterval", 15);
        feed.put("ldes:retentionPolicy", retentionPolicy(feedUri));
        feed.put("dct:license", id(licenseUri.toString()));
        feed.put("tree:member", members);

        try {
            return objectMapper.writeValueAsString(feed);
        } catch (JsonProcessingException exception) {
            throw new InternalProcessingException("Failed to serialize the Linked Connections feed", exception);
        }
    }

    private Map<String, Object> retentionPolicy(URI feedUri) {
        Map<String, Object> policy = new LinkedHashMap<>();
        policy.put("@id", feedUri + "#retention");
        policy.put("fullLogDuration", eventLog.getRetention().toString());
        return policy;
    }

    private Map<String, Object> context() {
        Map<String, Object> context = new LinkedHashMap<>();
        context.put("ldes", "https://w3id.org/ldes#");
        context.put("tree", "https://w3id.org/tree#");
        context.put("lc", "http://semweb.mmlab.be/ns/linkedconnections#");
        context.put("gtfs", "http://vocab.gtfs.org/terms#");
        context.put("dct", "http://purl.org/dc/terms/");
        context.put("xsd", "http://www.w3.org/2001/XMLSchema#");
        context.put("Connection", "lc:Connection");
        context.put("CancelledConnection", "lc:CancelledConnection");
        context.put("departureStop", iriTerm("lc:departureStop"));
        context.put("arrivalStop", iriTerm("lc:arrivalStop"));
        context.put("departureTime", typedTerm("lc:departureTime", "xsd:dateTime"));
        context.put("arrivalTime", typedTerm("lc:arrivalTime", "xsd:dateTime"));
        context.put("departureDelay", typedTerm("lc:departureDelay", "xsd:integer"));
        context.put("arrivalDelay", typedTerm("lc:arrivalDelay", "xsd:integer"));
        context.put("modified", typedTerm("dct:modified", "xsd:dateTime"));
        context.put("isVersionOf", iriTerm("dct:isVersionOf"));
        context.put("fullLogDuration", typedTerm("ldes:fullLogDuration", "xsd:duration"));
        context.put("pollingInterval", typedTerm("ldes:pollingInterval", "xsd:integer"));
        context.put("gtfs:trip", Map.of("@type", "@id"));
        context.put("gtfs:route", Map.of("@type", "@id"));
        context.put("gtfs:pickupType", Map.of("@type", "@id"));
        context.put("gtfs:dropOffType", Map.of("@type", "@id"));
        return context;
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
}
