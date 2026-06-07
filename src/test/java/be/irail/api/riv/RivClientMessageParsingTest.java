package be.irail.api.riv;

import be.irail.api.dto.Message;
import be.irail.api.dto.MessageLink;
import be.irail.api.dto.MessageType;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import java.io.InputStream;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

class RivClientMessageParsingTest {

    private final ObjectMapper objectMapper = new ObjectMapper();

    // Concrete subclass to test the protected parseMessages method
    private final RivClient rivClient = new RivClient() {};

    private JsonNode loadTestData(String resource) throws Exception {
        try (InputStream is = getClass().getResourceAsStream(resource)) {
            return objectMapper.readTree(is);
        }
    }

    @Test
    void parseMessages_journeyLegWithMessages_returnsParsedAlerts() throws Exception {
        JsonNode legNode = loadTestData("journey_leg_with_messages.json");

        List<Message> messages = rivClient.parseMessages(legNode);

        assertFalse(messages.isEmpty(), "Expected at least one message");

        Message first = messages.getFirst();
        assertEquals("105407", first.getId());
        assertEquals("Luttre", first.getHeader());
        assertNotNull(first.getMessage());
        assertTrue(first.getMessage().contains("replacement") || first.getMessage().contains("replaces"),
                "Expected message about replacement bus service");
        assertEquals(MessageType.WORKS, first.getType());
        assertNotNull(first.getValidFrom());
        assertNotNull(first.getValidUpTo());
    }

    @Test
    void parseMessages_journeyLegWithMessages_parsesLinks() throws Exception {
        JsonNode legNode = loadTestData("journey_leg_with_messages.json");

        List<Message> messages = rivClient.parseMessages(legNode);
        Message first = messages.getFirst();

        List<MessageLink> links = first.getLinks();
        assertFalse(links.isEmpty(), "Expected at least one link");
        assertTrue(links.stream().anyMatch(l -> l.getLink().contains("belgiantrain.be")),
                "Expected a link to belgiantrain.be");
    }

    @Test
    void parseMessages_vehicleDetailWithMessages_returnsParsedAlerts() throws Exception {
        JsonNode detailNode = loadTestData("vehicle_detail_with_messages.json");

        List<Message> messages = rivClient.parseMessages(detailNode);

        assertFalse(messages.isEmpty(), "Expected at least one message");
        assertEquals("105407", messages.getFirst().getId());
    }

    @Test
    void parseMessages_nodeWithoutMessages_returnsEmptyList() throws Exception {
        JsonNode emptyNode = objectMapper.readTree("{}");

        List<Message> messages = rivClient.parseMessages(emptyNode);

        assertTrue(messages.isEmpty());
    }

    @Test
    void parseMessages_multipleMessages_parsesAll() throws Exception {
        JsonNode legNode = loadTestData("journey_leg_with_messages.json");

        List<Message> messages = rivClient.parseMessages(legNode);

        assertEquals(2, messages.size(), "Expected two messages (works + bus stop relocation)");
        // Second message is about bus stop relocation
        Message second = messages.get(1);
        assertEquals("107137", second.getId());
        assertTrue(second.getHeader().contains("bus stop relocated"),
                "Expected header about bus stop relocation");
    }
}
