package be.irail.api.exception;

import be.irail.api.config.Metrics;
import com.codahale.metrics.Meter;
import com.fasterxml.jackson.annotation.*;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.ws.rs.NotFoundException;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;

import java.util.Arrays;
import java.util.List;

@Provider
public class IrailExceptionMapper implements ExceptionMapper<Throwable> {

    private final Meter irailBadRequestMeter = Metrics.getRegistry().meter("Exceptions, Irail, Bad request");
    private final Meter irailNotFoundMeter = Metrics.getRegistry().meter("Exceptions, Irail, Journey or station not found");
    private final Meter irailExceptionMeter = Metrics.getRegistry().meter("Exceptions, Irail");
    private final Meter exceptionMeter = Metrics.getRegistry().meter("Exceptions, unchecked");
    private final Meter notFoundMeter = Metrics.getRegistry().meter("404 Not found");

    @Override
    public Response toResponse(Throwable throwable) {
        while (throwable instanceof UncheckedExecutionException) {
            throwable = throwable.getCause();
        }
        if (throwable instanceof IrailHttpException exception) {
            if (exception.getHttpCode() == 400) {
                irailBadRequestMeter.mark();
                return Response.status(exception.getHttpCode()).header("Content-Type", "application/json").entity(new ExceptionDto(exception.getMessage(), exception)).build();
            } else if (exception.getHttpCode() == 404) {
                irailNotFoundMeter.mark();
                return Response.status(exception.getHttpCode()).header("Content-Type", "application/json").entity(new ExceptionDto(exception.getMessage(), exception)).build();
            }
            irailExceptionMeter.mark();
            return Response.status(exception.getHttpCode()).header("Content-Type", "application/json").entity(new ExceptionDto(exception)).build();
        }
        if (throwable instanceof NotFoundException exception) {
            // Dont print a stacktrace for 404
            notFoundMeter.mark();
            return Response.status(404).header("Content-Type", "application/json").entity(new ExceptionDto(exception.getMessage(), exception)).build();
        }
        exceptionMeter.mark();
        return Response.status(500).header("Content-Type", "application/json").entity(new ExceptionDto(throwable)).build();
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
            this.at = throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber();
            this.stackTrace = Arrays.stream(throwable.getStackTrace()).map(s -> s.getClassName() + ", " + s.getMethodName() + "() in " + s.getFileName() + ":" + s.getLineNumber()).toList();
        }

        private ExceptionDto(String message, Throwable throwable) {
            this.exception = throwable.getClass().getSimpleName();
            this.message = message;
            this.cause = null;
            this.at = throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber();
            this.stackTrace = List.of();
        }
    }
}