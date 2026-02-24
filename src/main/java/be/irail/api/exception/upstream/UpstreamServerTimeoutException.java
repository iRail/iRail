package be.irail.api.exception.upstream;

public class UpstreamServerTimeoutException extends UpstreamServerException {
    public UpstreamServerTimeoutException() {
        super("A timeout occurred while communicating with an upstream server");
    }
}
