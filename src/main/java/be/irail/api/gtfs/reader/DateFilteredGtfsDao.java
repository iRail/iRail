package be.irail.api.gtfs.reader;

import org.onebusaway.gtfs.impl.GtfsDaoImpl;
import org.onebusaway.gtfs.model.ServiceCalendarDate;

import java.time.LocalDate;

/**
 * Discards unused calendar dates before they are retained in the GTFS entity store.
 */
class DateFilteredGtfsDao extends GtfsDaoImpl {

    private final LocalDate firstDate;
    private final LocalDate lastDate;

    DateFilteredGtfsDao(LocalDate firstDate, LocalDate lastDate) {
        this.firstDate = firstDate;
        this.lastDate = lastDate;
    }

    @Override
    public void saveEntity(Object entity) {
        if (entity instanceof ServiceCalendarDate calendarDate) {
            if (calendarDate.getExceptionType() != ServiceCalendarDate.EXCEPTION_TYPE_ADD) {
                return;
            }
            LocalDate date = LocalDate.of(calendarDate.getDate().getYear(),
                    calendarDate.getDate().getMonth(), calendarDate.getDate().getDay());
            if (date.isBefore(firstDate) || date.isAfter(lastDate)) {
                return;
            }
        }
        super.saveEntity(entity);
    }
}
