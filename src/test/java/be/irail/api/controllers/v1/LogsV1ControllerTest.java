package be.irail.api.controllers.v1;

import be.irail.api.db.LogQueryType;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.EnumSource;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotNull;

class LogsV1ControllerTest {

    @Test
    void publishesTheNamesV1HasAlwaysUsed() {
        // Three of these differ from LogQueryType.getValue(), which carries the upstream NMBS wording.
        // Publishing the enum value instead would silently rename them for every existing consumer.
        assertEquals("Connections", LogsV1Controller.v1Name(LogQueryType.JOURNEYPLANNING));
        assertEquals("VehicleInformation", LogsV1Controller.v1Name(LogQueryType.DATEDVEHICLEJOURNEY));
        assertEquals("Disturbances", LogsV1Controller.v1Name(LogQueryType.SERVICEALERTS));

        assertEquals("Liveboard", LogsV1Controller.v1Name(LogQueryType.LIVEBOARD));
        assertEquals("Composition", LogsV1Controller.v1Name(LogQueryType.VEHICLECOMPOSITION));
        assertEquals("Stations", LogsV1Controller.v1Name(LogQueryType.STATIONS));
    }

    @ParameterizedTest
    @EnumSource(LogQueryType.class)
    void everyQueryTypeHasAPublishedName(LogQueryType queryType) {
        assertNotNull(LogsV1Controller.v1Name(queryType));
    }
}
