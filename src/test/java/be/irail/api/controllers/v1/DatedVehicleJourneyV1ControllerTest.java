package be.irail.api.controllers.v1;

import org.junit.jupiter.api.Test;

import java.time.LocalDate;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertTrue;

class DatedVehicleJourneyV1ControllerTest {

    private static final LocalDate TODAY = LocalDate.of(2026, 5, 20);

    @Test
    void usesRivForCurrentDate() {
        assertFalse(DatedVehicleJourneyV1Controller.usesGtfs(TODAY, TODAY));
    }

    @Test
    void usesGtfsForPastDate() {
        assertTrue(DatedVehicleJourneyV1Controller.usesGtfs(TODAY.minusDays(1), TODAY));
    }

    @Test
    void usesGtfsForFutureDate() {
        assertTrue(DatedVehicleJourneyV1Controller.usesGtfs(TODAY.plusDays(1), TODAY));
    }
}
