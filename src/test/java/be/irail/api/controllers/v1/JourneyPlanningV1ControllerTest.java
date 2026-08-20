package be.irail.api.controllers.v1;

import be.irail.api.exception.request.BadRequestException;
import be.irail.api.legacy.DataRoot;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertArrayEquals;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertSame;
import static org.junit.jupiter.api.Assertions.assertThrows;

class JourneyPlanningV1ControllerTest {

    private static DataRoot connectionsDataRoot(int count) {
        DataRoot dataRoot = new DataRoot("connections");
        Object[] connections = new Object[count];
        for (int i = 0; i < count; i++) {
            connections[i] = "connection-" + i;
        }
        dataRoot.connection = connections;
        return dataRoot;
    }

    @Test
    void parseResultsWithoutValueReturnsNoLimit() {
        assertNull(JourneyPlanningV1Controller.parseResults(null));
        assertNull(JourneyPlanningV1Controller.parseResults(""));
        assertNull(JourneyPlanningV1Controller.parseResults("  "));
    }

    @Test
    void parseResultsAcceptsPositiveNumbers() {
        assertEquals(1, JourneyPlanningV1Controller.parseResults("1"));
        assertEquals(3, JourneyPlanningV1Controller.parseResults(" 3 "));
    }

    @Test
    void parseResultsRejectsZeroAndNegativeNumbers() {
        assertThrows(BadRequestException.class, () -> JourneyPlanningV1Controller.parseResults("0"));
        assertThrows(BadRequestException.class, () -> JourneyPlanningV1Controller.parseResults("-2"));
    }

    @Test
    void parseResultsRejectsNonNumericValues() {
        assertThrows(BadRequestException.class, () -> JourneyPlanningV1Controller.parseResults("all"));
        assertThrows(BadRequestException.class, () -> JourneyPlanningV1Controller.parseResults("1.5"));
    }

    @Test
    void limitConnectionsTruncatesToRequestedSize() {
        DataRoot full = connectionsDataRoot(6);

        DataRoot limited = JourneyPlanningV1Controller.limitConnections(full, 2);

        assertArrayEquals(new Object[]{"connection-0", "connection-1"}, (Object[]) limited.connection);
        assertEquals(full.version, limited.version);
        assertEquals(full.timestamp, limited.timestamp);
        assertEquals(full.getRootName(), limited.getRootName());
    }

    @Test
    void limitConnectionsLeavesTheCachedResultUntouched() {
        DataRoot full = connectionsDataRoot(6);

        JourneyPlanningV1Controller.limitConnections(full, 2);

        assertEquals(6, ((Object[]) full.connection).length);
    }

    @Test
    void limitConnectionsWithoutLimitReturnsTheSameInstance() {
        DataRoot full = connectionsDataRoot(6);

        assertSame(full, JourneyPlanningV1Controller.limitConnections(full, null));
    }

    @Test
    void limitConnectionsLargerThanTheResultSetReturnsEverything() {
        DataRoot full = connectionsDataRoot(3);

        DataRoot limited = JourneyPlanningV1Controller.limitConnections(full, 10);

        assertSame(full, limited);
        assertEquals(3, ((Object[]) limited.connection).length);
    }

    @Test
    void limitConnectionsHandlesAResultWithoutConnections() {
        DataRoot empty = new DataRoot("connections");

        assertSame(empty, JourneyPlanningV1Controller.limitConnections(empty, 1));
    }
}
