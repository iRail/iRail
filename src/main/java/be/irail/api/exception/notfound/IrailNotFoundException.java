package be.irail.api.exception.notfound;

import be.irail.api.exception.IrailHttpException;

public abstract class IrailNotFoundException extends IrailHttpException {
    public IrailNotFoundException(int httpCode, String message) {
        super(httpCode, message);
    }

    public IrailNotFoundException(int httpCode, String message, Throwable cause) {
        super(httpCode, message, cause);
    }
}
