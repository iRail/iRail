package be.irail.api.exception.notfound;

import java.time.LocalDate;

public class CompositionNotFoundException extends IrailNotFoundException {
    public CompositionNotFoundException(String journeyId, LocalDate date) {
        super(404, "Could not find composition with id '" + journeyId + "' on date " + date);
    }
}
