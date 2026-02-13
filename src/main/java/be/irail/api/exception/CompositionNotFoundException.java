package be.irail.api.exception;

import java.time.LocalDate;
import java.time.LocalDateTime;

public class CompositionNotFoundException extends IrailHttpException {
    public CompositionNotFoundException(String journeyId, LocalDate date) {
        super(404, "Could not find composition with id '" + journeyId + "' on date " + date);
    }
}
