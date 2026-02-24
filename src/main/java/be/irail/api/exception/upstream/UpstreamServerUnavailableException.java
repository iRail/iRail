package be.irail.api.exception.upstream;

public class UpstreamServerUnavailableException extends UpstreamServerException {
    public UpstreamServerUnavailableException() {
        super("An upstream server is currently unavailable, try again later");
    }
}
