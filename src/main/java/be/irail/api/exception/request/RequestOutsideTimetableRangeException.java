package be.irail.api.exception.request;

import java.time.LocalDate;
import java.time.LocalDateTime;

public class RequestOutsideTimetableRangeException extends BadRequestException {
    public RequestOutsideTimetableRangeException(LocalDate queryDate) {
        super("Query date is outside timetable range", "date", queryDate.toString());
    }

    public RequestOutsideTimetableRangeException(LocalDateTime queryDate) {
        super("Query date is outside timetable range", "date", queryDate.toString());
    }
}
