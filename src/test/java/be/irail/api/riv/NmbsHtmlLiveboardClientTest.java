package be.irail.api.riv;

import be.irail.api.InMemoryTest;
import be.irail.api.db.OccupancyDao;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.riv.requests.LiveboardRequest;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.test.context.bean.override.mockito.MockitoBean;

import javax.sql.DataSource;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.anyInt;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.when;

class NmbsHtmlLiveboardClientTest extends InMemoryTest {

    @Autowired
    private StationsDao stationsDao;

    @MockitoBean
    private OccupancyDao occupancyDao;

    private GtfsInMemoryDao originalGtfsDao;
    private NmbsHtmlLiveboardClient client;

    @Autowired
    NmbsHtmlLiveboardClientTest(DataSource dataSource) {
        super(dataSource);
    }

    @BeforeEach
    void setUp() {
        when(occupancyDao.getOccupancy(any(DepartureOrArrival.class))).thenReturn(new OccupancyInfo(null, null));

        // Mock GtfsInMemoryDao singleton
        originalGtfsDao = GtfsInMemoryDao.getInstance();
        GtfsInMemoryDao gtfsMock = mock(GtfsInMemoryDao.class);
        when(gtfsMock.getStartDate(anyInt(), any(LocalDateTime.class)))
                .thenReturn(Optional.of(LocalDate.of(2023, 12, 15)));
        GtfsInMemoryDao.setInstance(gtfsMock);

        client = new NmbsHtmlLiveboardClient(stationsDao, occupancyDao);
    }

    @AfterEach
    void tearDown() {
        GtfsInMemoryDao.setInstance(originalGtfsDao);
    }

    @Test
    void testDepartureBoardNormalCase_shouldParseDataCorrectly() throws IOException {
        String rawHtml = readTestFile("NmbsHtmlLiveboardRepositoryTest_departuresAntwerpen.html");

        Station antwerpenStation = stationsDao.getStationFromId("008821006");

        StationDto currentStationDto = new StationDto(
                "008821006", "http://irail.be/stations/NMBS/008821006",
                "Antwerpen-Centraal", "Antwerpen-Centraal", 4.421101, 51.2172);

        LiveboardRequest request = new LiveboardRequest(
                antwerpenStation,
                LocalDateTime.of(2023, 12, 15, 12, 58),
                TimeSelection.DEPARTURE,
                Language.NL
        );

        String cleanedHtml = client.cleanResponse(rawHtml);
        List<DepartureOrArrival> stops = client.parseNmbsData(request, currentStationDto, cleanedHtml);

        assertEquals(50, stops.size());
        assertEquals("008821006", stops.get(0).getStation().getId());
        assertEquals("Antwerpen-Centraal", stops.get(0).getStation().getStationName());
        assertEquals("008833001", stops.get(0).getVehicle().getDirection().getStation().getId());
        assertEquals("Leuven", stops.get(0).getVehicle().getDirection().getName());
        assertEquals(LocalDateTime.of(2023, 12, 15, 12, 58), stops.get(0).getScheduledDateTime());
        assertEquals("http://irail.be/connections/8821006/20231215/L2862", stops.get(0).getDepartureUri());

        assertEquals("008400058", stops.get(12).getVehicle().getDirection().getStation().getId());
        assertEquals("Amsterdam Cs (NL)", stops.get(12).getVehicle().getDirection().getName());
    }

    @Test
    void testDepartureBoardPlatformChanges_shouldParsePlatformsCorrectly() throws IOException {
        String rawHtml = readTestFile("NmbsHtmlLiveboardRepositoryTest_platformChanges.html");

        Station brusselStation = stationsDao.getStationFromId("008814001");

        StationDto currentStationDto = new StationDto(
                "008814001", "http://irail.be/stations/NMBS/008814001",
                "Brussel-Zuid/Bruxelles-Midi", "Brussel-Zuid/Bruxelles-Midi", 4.336531, 50.8354);

        LiveboardRequest request = new LiveboardRequest(
                brusselStation,
                LocalDateTime.of(2024, 1, 28, 15, 50),
                TimeSelection.DEPARTURE,
                Language.NL
        );

        String cleanedHtml = client.cleanResponse(rawHtml);
        List<DepartureOrArrival> stops = client.parseNmbsData(request, currentStationDto, cleanedHtml);

        assertEquals(52, stops.size());
        assertEquals("6", stops.get(0).getPlatform().getDesignation());
        assertTrue(stops.get(0).getPlatform().hasChanged());
        assertEquals("16", stops.get(1).getPlatform().getDesignation());
        assertFalse(stops.get(1).getPlatform().hasChanged());
        assertEquals("?", stops.get(47).getPlatform().getDesignation());
        assertFalse(stops.get(47).getPlatform().hasChanged());
    }

    private String readTestFile(String filename) throws IOException {
        try (InputStream is = getClass().getResourceAsStream(filename)) {
            if (is == null) {
                throw new IOException("Fixture not found: " + filename);
            }
            return new String(is.readAllBytes(), StandardCharsets.UTF_8);
        }
    }
}
