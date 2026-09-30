package be.irail.api.gtfs.dao;

import be.irail.api.gtfs.reader.GtfsReader;
import be.irail.api.gtfs.reader.models.CalendarDate;
import be.irail.api.gtfs.reader.models.Trip;
import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.List;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.assertEquals;

class GtfsInMemoryDaoTest {

    private static final LocalDate MONDAY = LocalDate.of(2026, 8, 17);
    private static final LocalDate TUESDAY = LocalDate.of(2026, 8, 18);

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

    @Test
    void tripIdBody_stripsOnlyTheTrailingDate() {
        assertEquals("gt:nmbssncb:88____:UUU::8775100:8814001:8:1834",
                GtfsInMemoryDao.tripIdBody("gt:nmbssncb:88____:UUU::8775100:8814001:8:1834:20260810"));
        assertEquals("no-separators", GtfsInMemoryDao.tripIdBody("no-separators"));
    }

    @Test
    void tripIdMatchKey_dropsTheFeedNamespaceAndTheTrailingDate() {
        // The Belgian Mobility feeds namespace their ids, the hafas feed does not. Both must reduce
        // to the same key or a realtime trip can never be found in the schedule.
        assertEquals("88____:UUU::8775100:8814001:8:1834",
                GtfsInMemoryDao.tripIdMatchKey("gt:nmbssncb:88____:UUU::8775100:8814001:8:1834:20260810"));
        assertEquals("88____:UUU::8775100:8814001:8:1834",
                GtfsInMemoryDao.tripIdMatchKey("88____:UUU::8775100:8814001:8:1834:20260819"));
    }

    @Test
    void resolveTripIdForServiceDate_matchesAnIdWithoutTheFeedNamespace() {
        GtfsInMemoryDao dao = daoWith(
                trip("gt:nmbssncb:88____:007::8814001:8892007:3:1105:20260810", "service-monday"),
                List.of(new CalendarDate("service-monday", MONDAY)));

        assertEquals(Optional.of("gt:nmbssncb:88____:007::8814001:8892007:3:1105:20260810"),
                dao.resolveTripIdForServiceDate("88____:007::8814001:8892007:3:1105:20260817", MONDAY));
    }

    @Test
    void resolveTripIdForServiceDate_matchesAcrossDifferingTrailingDates() {
        // The static feed stamps a trip id with a date identifying the service pattern, while
        // GTFS-Realtime puts the actual service date there. Comparing the two as strings never matches.
        GtfsInMemoryDao dao = daoWith(
                trip("gt:nmbssncb:88____:007::8814001:8892007:3:1105:20260810", "service-monday"),
                List.of(new CalendarDate("service-monday", MONDAY)));

        assertEquals(Optional.of("gt:nmbssncb:88____:007::8814001:8892007:3:1105:20260810"),
                dao.resolveTripIdForServiceDate("gt:nmbssncb:88____:007::8814001:8892007:3:1105:20260817", MONDAY));
    }

    @Test
    void resolveTripIdForServiceDate_picksTheCandidateRunningThatDay() {
        // One route body, two service patterns — only the calendar tells them apart.
        String body = "gt:nmbssncb:BBUS__:049::8885001:8886009:3:558";
        GtfsReader.GtfsData data = new GtfsReader.GtfsData(null, List.of(),
                List.of(new CalendarDate("service-monday", MONDAY), new CalendarDate("service-tuesday", TUESDAY)),
                List.of(), List.of(), List.of(),
                List.of(new Trip(body + ":20260621", "route-id", "service-monday", "Brugge", 1905, 0, null),
                        new Trip(body + ":20260628", "route-id", "service-tuesday", "Brugge", 1905, 0, null)));
        GtfsInMemoryDao dao = new GtfsInMemoryDao(data);

        assertEquals(Optional.of(body + ":20260621"), dao.resolveTripIdForServiceDate(body + ":20260817", MONDAY));
        assertEquals(Optional.of(body + ":20260628"), dao.resolveTripIdForServiceDate(body + ":20260818", TUESDAY));
    }

    @Test
    void resolveTripIdForServiceDate_isEmptyWhenNothingRunsThatDay() {
        GtfsInMemoryDao dao = daoWith(trip("trip:20260810", "service-monday"),
                List.of(new CalendarDate("service-monday", MONDAY)));

        assertEquals(Optional.empty(), dao.resolveTripIdForServiceDate("trip:20260818", TUESDAY));
        assertEquals(Optional.empty(), dao.resolveTripIdForServiceDate("unknown:trip:20260817", MONDAY));
    }

    @Test
    void resolveTripIdForServiceDate_returnsAKnownStaticIdUnchanged() {
        GtfsInMemoryDao dao = daoWith(trip("trip:20260810", "service-monday"),
                List.of(new CalendarDate("service-monday", MONDAY)));

        assertEquals(Optional.of("trip:20260810"), dao.resolveTripIdForServiceDate("trip:20260810", MONDAY));
    }

    @Test
    void resolveTripIdForServiceDate_breaksTiesDeterministically() {
        // A handful of bodies really do have two patterns active on the same day. Whichever is chosen,
        // it must be the same one on every refresh, or delays would flip between feed reads.
        String body = "gt:nmbssncb:BBUS__:049::8885001:8886009:3:558";
        GtfsReader.GtfsData data = new GtfsReader.GtfsData(null, List.of(),
                List.of(new CalendarDate("service-a", MONDAY), new CalendarDate("service-b", MONDAY)),
                List.of(), List.of(), List.of(),
                List.of(new Trip(body + ":20260628", "route-id", "service-b", "Brugge", 1905, 0, null),
                        new Trip(body + ":20260621", "route-id", "service-a", "Brugge", 1905, 0, null)));
        GtfsInMemoryDao dao = new GtfsInMemoryDao(data);

        assertEquals(Optional.of(body + ":20260621"), dao.resolveTripIdForServiceDate(body + ":20260817", MONDAY));
    }

    private static Trip trip(String tripId, String serviceId) {
        return new Trip(tripId, "route-id", serviceId, "Brussels", 1234, 0, null);
    }

    private static GtfsInMemoryDao daoWith(Trip trip, List<CalendarDate> calendarDates) {
        return new GtfsInMemoryDao(new GtfsReader.GtfsData(
                null, List.of(), calendarDates, List.of(), List.of(), List.of(), List.of(trip)));
    }
}
