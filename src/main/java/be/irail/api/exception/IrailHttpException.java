package be.irail.api.exception;

public class IrailHttpException extends RuntimeException {
    private final int httpCode;

    public IrailHttpException(int httpCode, String message) {
        super(message);
        this.httpCode = httpCode;
    }

    public IrailHttpException(int httpCode, String message, Throwable cause) {
        super(message, cause);
        this.httpCode = httpCode;
    }

    public int getHttpCode() {
        return httpCode;
    }
}
