package be.irail.api.exception;

import be.irail.api.config.Metrics;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.exception.upstream.UpstreamServerParameterException;
import com.codahale.metrics.Meter;
import com.fasterxml.jackson.annotation.JsonInclude;
import com.fasterxml.jackson.annotation.JsonProperty;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.ws.rs.NotFoundException;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;

import java.util.Arrays;
import java.util.List;

@Provider
public class IrailExceptionMapper implements ExceptionMapper<Throwable> {
    private static final Logger log = LogManager.getLogger(IrailExceptionMapper.class);

    private final Meter irailBadRequestMeter = Metrics.getRegistry().meter("Exceptions, Irail, Bad request");
    private final Meter irailNotFoundMeter = Metrics.getRegistry().meter("Exceptions, Irail, Journey or station not found");
    private final Meter irailExceptionMeter = Metrics.getRegistry().meter("Exceptions, Irail");
    private final Meter upstreamBadRequestExceptionMeter = Metrics.getRegistry().meter("Exceptions, Upstream, Bad request");
    private final Meter upstreamExceptionMeter = Metrics.getRegistry().meter("Exceptions, Upstream");
    private final Meter exceptionMeter = Metrics.getRegistry().meter("Exceptions, unchecked");
    private final Meter notFoundMeter = Metrics.getRegistry().meter("404 Not found");

    @Override
    @Produces("application/json")
    public Response toResponse(Throwable throwable) {
        while (throwable instanceof UncheckedExecutionException) {
            throwable = throwable.getCause();
        }
        if (throwable instanceof IrailHttpException exception) {
            if (throwable instanceof UpstreamServerParameterException) {
                // No full stack trace needed
                upstreamBadRequestExceptionMeter.mark();
                return getJsonResponse(exception.getHttpCode(), new ExceptionDto(exception.getMessage(), exception));
            } else if (throwable instanceof UpstreamServerException) {
                // No full stack trace needed
                upstreamExceptionMeter.mark();
                return getJsonResponse(exception.getHttpCode(), new ExceptionDto(exception.getMessage(), exception));
            } else if (exception.getHttpCode() == 400) {
                irailBadRequestMeter.mark();
                return getJsonResponse(exception.getHttpCode(), new ExceptionDto(exception.getMessage(), exception));
            } else if (exception.getHttpCode() == 404) {
                irailNotFoundMeter.mark();
                return getJsonResponse(exception.getHttpCode(), new ExceptionDto(exception.getMessage(), exception));
            }
            log.error("Exception occurred during HTTP request: {}", throwable.getMessage(), throwable);
            irailExceptionMeter.mark();
            return getJsonResponse(exception.getHttpCode(), new ExceptionDto(exception));
        }
        if (throwable instanceof NotFoundException exception) {
            // Dont print a stacktrace for 404
            notFoundMeter.mark();
            return getJsonResponse(404, new ExceptionDto(exception.getMessage(), exception));
        }
        log.error("Exception occurred during HTTP request: {}", throwable.getMessage(), throwable);
        exceptionMeter.mark();
        return getJsonResponse(500, new ExceptionDto(throwable));
    }

    private static Response getJsonResponse(int exception, ExceptionDto exception1) {
        return Response.status(exception).entity(exception1).build();
    }


    private static final class ExceptionDto {

        @JsonProperty
        private final String exception;
        @JsonProperty
        private final String message;
        @JsonProperty()
        private final String at;
        @JsonProperty
        private final List<String> stackTrace;
        @JsonProperty
        @JsonInclude(JsonInclude.Include.NON_NULL)
        private final ExceptionDto cause;

        private ExceptionDto(Throwable throwable) {
            this(throwable, false);
        }

        private ExceptionDto(Throwable throwable, boolean skipCause) {
            this.exception = throwable.getClass().getSimpleName();
            this.message = throwable.getMessage();
            this.cause = throwable.getCause() != null ? new ExceptionDto(throwable.getCause(), true) : null;
            this.at = throwable.getStackTrace().length > 0 ? throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber() : "";
            this.stackTrace = Arrays.stream(throwable.getStackTrace()).map(s -> s.getClassName() + ", " + s.getMethodName() + "() in " + s.getFileName() + ":" + s.getLineNumber()).toList();
        }

        private ExceptionDto(String message, Throwable throwable) {
            this.exception = throwable.getClass().getSimpleName();
            this.message = message;
            this.cause = null;
            this.at = throwable.getStackTrace().length > 0 ? throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber() : "";
            this.stackTrace = List.of();
        }
    }
}