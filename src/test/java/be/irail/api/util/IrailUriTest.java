package be.irail.api.util;

import org.junit.jupiter.api.Test;

import java.time.LocalDate;

import static org.junit.jupiter.api.Assertions.assertEquals;

class IrailUriTest {
    @Test
    void mintsTheCanonicalConnectionIriFromAnApiStationId() {
        assertEquals("http://irail.be/connections/8821006/20231215/L2862",
                IrailUri.connection("008821006", LocalDate.of(2023, 12, 15), "L 2862"));
    }

    @Test
    void acceptsAnAlreadyNormalizedHafasId() {
        assertEquals("http://irail.be/connections/8814001/20260820/IC123",
                IrailUri.connection("8814001", LocalDate.of(2026, 8, 20), "IC123"));
    }
}
