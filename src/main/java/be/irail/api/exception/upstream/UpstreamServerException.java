package be.irail.api.exception.upstream;

import be.irail.api.exception.IrailHttpException;

public class UpstreamServerException extends IrailHttpException {
    public UpstreamServerException(String message) {
        super(504, "An upstream server encountered an issue while handling this request: " + message);
    }
    public UpstreamServerException(String message, Exception e) {
        super(504, "An upstream server encountered an issue while handling this request: " + message, e);
    }

}
