package be.irail.api.exception.upstream;

public class UpstreamRateLimitException extends UpstreamServerException {
    public UpstreamRateLimitException() {
        super("Outgoing request to an upstream server was rate limited, try again later");
    }
}
