package be.irail.api.exception;

import java.time.LocalDate;
import java.time.LocalDateTime;

public class JourneyNotFoundException extends IrailHttpException {
    public JourneyNotFoundException(String journeyId, LocalDate date) {
        super(404, "Could not find journey with id '" + journeyId + "' on date " + date);
    }

    public JourneyNotFoundException(int journeyId, LocalDate date) {
        this(String.valueOf(journeyId), date);
    }

    public JourneyNotFoundException(String journeyId, LocalDate date, String message) {
        this(journeyId, date.atStartOfDay(), message);
    }

    public JourneyNotFoundException(int journeyId, LocalDateTime date, String message) {
        this(String.valueOf(journeyId), date, message);
    }

    public JourneyNotFoundException(String journeyId, LocalDateTime date, String message) {
        super(404, "Could not find journey with id '" + journeyId + "' at " + date + ". " + message);
    }
}
