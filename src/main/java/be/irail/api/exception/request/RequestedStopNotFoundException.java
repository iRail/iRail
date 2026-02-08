package be.irail.api.exception.request;

public class RequestedStopNotFoundException extends BadRequestException {

    public RequestedStopNotFoundException(String field, String value) {
        super("Requested stop not found: " + value, field, value);
    }
}
