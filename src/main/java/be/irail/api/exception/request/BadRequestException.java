package be.irail.api.exception.request;

import be.irail.api.exception.IrailHttpException;

public class BadRequestException extends IrailHttpException {
    public BadRequestException(String message, String parameter) {
        super(400, "Invalid request, parameter '" + parameter + "' is not set correctly: " + message);
    }

    public BadRequestException(String message, String parameter, String value) {
        super(400, "Invalid request, value '" + value + "' for parameter '" + parameter + "' is invalid: " + message);
    }
}
