package be.irail.api.riv;

import be.irail.api.exception.upstream.UpstreamServerException;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;

class NmbsRivJourneyPlanningClientTest {

    private final ObjectMapper objectMapper = new ObjectMapper();

    private JsonNode json(String raw) throws Exception {
        return objectMapper.readTree(raw);
    }

    @Test
    void returnsTheTripArrayOfARegularResponse() throws Exception {
        JsonNode trips = NmbsRivJourneyPlanningClient.requireTripNode(json("{\"Trip\": [{\"idx\": \"0\"}, {\"idx\": \"1\"}]}"));

        assertTrue(trips.isArray());
        assertEquals(2, trips.size());
    }

    @Test
    void returnsASingleTripThatIsNotWrappedInAnArray() throws Exception {
        JsonNode trips = NmbsRivJourneyPlanningClient.requireTripNode(json("{\"Trip\": {\"idx\": \"0\"}}"));

        assertTrue(trips.isObject());
    }

    @Test
    void anEmptyTripArrayIsAValidResponse() throws Exception {
        JsonNode trips = NmbsRivJourneyPlanningClient.requireTripNode(json("{\"Trip\": []}"));

        assertTrue(trips.isArray());
        assertEquals(0, trips.size());
    }

    @Test
    void throwsWhenTheResponseCarriesNoTripNode() throws Exception {
        // What the security layer returns for "7601 : _Threat.Requests : Enhanced Security request
        // violation". Previously this parsed cleanly into zero connections, so callers received an
        // empty result set with HTTP 200 instead of an error.
        JsonNode securityViolation = json("{\"errorCode\": 7601, \"errorText\": \"_Threat.Requests : Enhanced Security request violation\"}");

        assertThrows(UpstreamServerException.class, () -> NmbsRivJourneyPlanningClient.requireTripNode(securityViolation));
    }

    @Test
    void throwsWhenTheResponseIsAnEmptyObject() throws Exception {
        assertThrows(UpstreamServerException.class, () -> NmbsRivJourneyPlanningClient.requireTripNode(json("{}")));
    }

    @Test
    void throwsWhenTheTripNodeIsExplicitlyNull() throws Exception {
        assertThrows(UpstreamServerException.class, () -> NmbsRivJourneyPlanningClient.requireTripNode(json("{\"Trip\": null}")));
    }
}
