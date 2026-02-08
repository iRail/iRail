package be.irail.api.exception;

import java.time.LocalDate;

public class JourneyNotFoundException extends IrailHttpException {
    public JourneyNotFoundException(String journeyId, LocalDate date) {
        super(404, "Could not find journey with id '" + journeyId + "' on date " + date);
    }

    public JourneyNotFoundException(String journeyId, LocalDate date, String message) {
        super(404, "Could not find journey with id '" + journeyId + "' on date " + date + ". " + message);
    }
}
