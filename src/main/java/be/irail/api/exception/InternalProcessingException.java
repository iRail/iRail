package be.irail.api.exception;

public class InternalProcessingException extends IrailHttpException {
    public InternalProcessingException(String message) {
        super(500, message);
    }

    public InternalProcessingException(String message, Throwable cause) {
        super(500, message, cause);
    }
}
