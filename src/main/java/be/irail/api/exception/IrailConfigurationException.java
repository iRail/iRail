package be.irail.api.exception;

public class IrailConfigurationException extends IrailHttpException {
    public IrailConfigurationException(String message) {
        super(500, message);
    }
}
