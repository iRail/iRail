package be.irail.api.gtfs.reader.models;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNull;

class StopTest {

    @Test
    void normalizesCurrentNamespacedIdentifiers() {
        assertEquals("8811304", Stop.getHafasId("gs:nmbssncb:S8811304"));
        assertEquals("8811130", Stop.getHafasId("gs:nmbssncb:8811130_3"));
    }

    @Test
    void normalizesLegacyIdentifiers() {
        assertEquals("8811304", Stop.getHafasId("S8811304"));
        assertEquals("8811304", Stop.getHafasId("8811304"));
    }

    @Test
    void rejectsNameBasedIdentifiers() {
        assertNull(Stop.getHafasId("nmbssncb:s-londonstpancrasgb"));
        assertNull(Stop.getHafasId(null));
    }
}
