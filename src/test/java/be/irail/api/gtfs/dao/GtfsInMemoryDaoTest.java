package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.GtfsReader;
import be.irail.api.gtfs.reader.models.CalendarDate;
import be.irail.api.gtfs.reader.models.Trip;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;

class GtfsInMemoryDaoTest {

    @Test
    void supportsNamespacedServiceIds() {
        String serviceId = "gc:nmbssncb:004359";
        LocalDate serviceDate = LocalDate.of(2026, 8, 14);
        GtfsReader.GtfsData data = new GtfsReader.GtfsData(
                null,
                List.of(),
                List.of(new CalendarDate(serviceId, serviceDate)),
                List.of(),
                List.of(),
                List.of(),
                List.of(new Trip("trip-id", "route-id", serviceId, "Brussels", 1234, 0, null))
        );

        GtfsInMemoryDao dao = new GtfsInMemoryDao(data);

        assertEquals(List.of(serviceDate), List.copyOf(dao.getCalendarDates(serviceId)));
        assertEquals("trip-id", dao.getTrip("trip-id").id());
    }
}
