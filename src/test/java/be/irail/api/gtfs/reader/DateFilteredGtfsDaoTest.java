package be.irail.api.gtfs.reader;

import org.junit.jupiter.api.Test;
import org.onebusaway.gtfs.model.AgencyAndId;
import org.onebusaway.gtfs.model.ServiceCalendarDate;
import org.onebusaway.gtfs.model.calendar.ServiceDate;

import java.time.LocalDate;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;

class DateFilteredGtfsDaoTest {

    @Test
    void onlyStoresAddedDatesWithinInclusiveMonthWindow() {
        LocalDate today = LocalDate.of(2026, 10, 31);
        LocalDate firstDate = today.minusMonths(1);
        LocalDate lastDate = today.plusMonths(4);
        DateFilteredGtfsDao dao = new DateFilteredGtfsDao(firstDate, lastDate);

        dao.saveEntity(calendarDate(1, firstDate.minusDays(1), ServiceCalendarDate.EXCEPTION_TYPE_ADD));
        dao.saveEntity(calendarDate(2, firstDate, ServiceCalendarDate.EXCEPTION_TYPE_ADD));
        dao.saveEntity(calendarDate(3, today, ServiceCalendarDate.EXCEPTION_TYPE_ADD));
        dao.saveEntity(calendarDate(4, lastDate, ServiceCalendarDate.EXCEPTION_TYPE_ADD));
        dao.saveEntity(calendarDate(5, lastDate.plusDays(1), ServiceCalendarDate.EXCEPTION_TYPE_ADD));
        dao.saveEntity(calendarDate(6, today, ServiceCalendarDate.EXCEPTION_TYPE_REMOVE));

        assertEquals(List.of(2, 3, 4), dao.getAllCalendarDates().stream()
                .map(ServiceCalendarDate::getId).sorted().toList());
    }

    private ServiceCalendarDate calendarDate(int id, LocalDate date, int exceptionType) {
        ServiceCalendarDate calendarDate = new ServiceCalendarDate();
        calendarDate.setId(id);
        calendarDate.setServiceId(new AgencyAndId("nmbs", "service"));
        calendarDate.setDate(new ServiceDate(date.getYear(), date.getMonthValue(), date.getDayOfMonth()));
        calendarDate.setExceptionType(exceptionType);
        return calendarDate;
    }
}
