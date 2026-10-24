package be.irail.api.config;

import be.irail.api.db.LogQueryType;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.CsvSource;
import org.junit.jupiter.params.provider.ValueSource;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNull;

class RequestLogFilterTest {

    @ParameterizedTest
    @CsvSource({
            "v1/liveboard,      LIVEBOARD",
            "v1/connections,    JOURNEYPLANNING",
            "v1/vehicle,        DATEDVEHICLEJOURNEY",
            "v1/stations,       STATIONS",
            "v1/composition,    VEHICLECOMPOSITION",
            "v1/disturbances,   SERVICEALERTS",
    })
    void recognisesTheV1DataQueries(String path, LogQueryType expected) {
        assertEquals(expected, RequestLogFilter.queryTypeFor(path));
    }

    @ParameterizedTest
    @CsvSource({
            "v2/liveboard,          LIVEBOARD",
            "v2/journeyplanning,    JOURNEYPLANNING",
            "v2/servicealerts,      SERVICEALERTS",
    })
    void recognisesTheV2NamesToo(String path, LogQueryType expected) {
        assertEquals(expected, RequestLogFilter.queryTypeFor(path));
    }

    @Test
    void ignoresTheLogsEndpointSoReadingLogsDoesNotLogItself() {
        assertNull(RequestLogFilter.queryTypeFor("v1/logs"));
        assertNull(RequestLogFilter.queryTypeFor("v1/logs/20260819"));
    }

    @ParameterizedTest
    @ValueSource(strings = {"", "/", "v1", "health", "v1/feedback/occupancy", "favicon.ico"})
    void ignoresEverythingThatIsNotADataQuery(String path) {
        assertNull(RequestLogFilter.queryTypeFor(path));
    }

    @Test
    void ignoresANullPath() {
        assertNull(RequestLogFilter.queryTypeFor(null));
    }

    @Test
    void toleratesATrailingSlashAndCasing() {
        assertEquals(LogQueryType.LIVEBOARD, RequestLogFilter.queryTypeFor("v1/liveboard/"));
        assertEquals(LogQueryType.LIVEBOARD, RequestLogFilter.queryTypeFor("v1/Liveboard"));
    }
}
