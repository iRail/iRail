package be.irail.api.db;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.test.context.ActiveProfiles;
import org.springframework.test.context.TestPropertySource;
import org.springframework.test.context.jdbc.Sql;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

@SpringBootTest
@ActiveProfiles("test")
@TestPropertySource(properties = {
        "spring.main.lazy-initialization=true",
        "spring.autoconfigure.exclude=org.springframework.boot.autoconfigure.task.TaskSchedulingAutoConfiguration"
})
@Sql(scripts = "/sql/stations-test-data.sql")
@Transactional
class StationsDaoTest {

    @Autowired
    private StationsDao dao;

    @BeforeEach
    void setUp() {
        dao.forceInitializeStations();
    }

    @Test
    void getStations_brussels_shouldReturnMultipleResults() {
        List<Station> stations = dao.getStations("Brussel");
        assertNotNull(stations);
        assertFalse(stations.isEmpty());
        assertEquals(6, stations.size());
        // Should be sorted by average departures
        assertEquals("Brussel-Zuid/Bruxelles-Midi", stations.getFirst().getName());
        assertTrue(stations.stream().allMatch(s -> s.getName().contains("Brussel")));
    }

    @Test
    void getStations_brusselsSouthDutchName_shouldReturnSingleResult() {
        // The dash is not required
        List<Station> stations = dao.getStations("Brussel Zuid");
        assertNotNull(stations);
        assertEquals(1, stations.size());
        assertEquals("008814001", stations.getFirst().getIrailId());
        assertEquals("8814001", stations.getFirst().getHafasId());
        assertEquals("Bruxelles-Midi", stations.getFirst().getAlternativeFr());
    }

    @Test
    void getStations_brusselsSouthFrenchName_shouldReturnSingleResult() {
        List<Station> stations = dao.getStations("Bruxelles-Midi");
        assertNotNull(stations);
        assertEquals(1, stations.size());
        assertEquals("008814001", stations.getFirst().getIrailId());
        assertEquals("8814001", stations.getFirst().getHafasId());
        assertEquals("Bruxelles-Midi", stations.getFirst().getAlternativeFr());
    }

    @Test
    void getStations_queryMissingAccents_shouldReturnAllMatchesIgnoringAccents() {
        List<Station> stations = dao.getStations("Moutiers Salins");
        assertNotNull(stations);
        assertEquals(1, stations.size());
        assertEquals("Moûtiers-Salins-Brides-les-Bai", stations.getFirst().getName());
    }

    @Test
    void getStations_queryWithAccents_shouldReturnAllMatchesIgnoringAccents() {
        List<Station> stations = dao.getStations("Moûtiers-Salins-Brides-les-Bai");
        assertNotNull(stations);
        assertEquals(1, stations.size());
        assertEquals("Moûtiers-Salins-Brides-les-Bai", stations.getFirst().getName());
    }

    @Test
    void getStationFromId() {
        Station station = dao.getStationFromId("008812005");
        assertNotNull(station);
        assertEquals("Brussel-Noord/Bruxelles-Nord", station.getName());
    }

    @Test
    void getAllStations() {
        List<Station> stations = dao.getAllStations();
        assertNotNull(stations);
        assertFalse(stations.isEmpty());
        assertTrue(stations.size() >= 3);
    }
}