package be.irail.api.exception;

import com.fasterxml.jackson.annotation.JsonProperty;
import jakarta.ws.rs.NotFoundException;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;

import java.util.Arrays;
import java.util.List;

@Provider
public class IrailExceptionMapper implements ExceptionMapper<Throwable> {

    @Override
    public Response toResponse(Throwable throwable) {
        if (throwable instanceof IrailHttpException exception) {
            if (exception.getHttpCode() >= 400 && exception.getHttpCode() < 500) {
                return Response.status(exception.getHttpCode()).header("Content-Type", "application/json").entity(new ExceptionDto(exception.getMessage(), exception)).build();
            }
            return Response.status(exception.getHttpCode()).header("Content-Type", "application/json").entity(new ExceptionDto(exception)).build();
        }
        if (throwable instanceof NotFoundException exception) {
            // Dont print a stacktrace for 404
            return Response.status(404).header("Content-Type", "application/json").entity(new ExceptionDto(exception.getMessage(), exception)).build();
        }
        return Response.status(500).header("Content-Type", "application/json").entity(new ExceptionDto(throwable)).build();
    }


    private static final class ExceptionDto {

        @JsonProperty
        private final String message;
        @JsonProperty
        private final String cause;
        @JsonProperty
        private final String at;
        @JsonProperty
        private final List<String> stackTrace;

        private ExceptionDto(Throwable throwable) {
            this.message = throwable.getMessage();
            this.cause = throwable.getCause() != null ? throwable.getCause().getMessage() : "";
            this.at = throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber();
            this.stackTrace = Arrays.stream(throwable.getStackTrace()).map(s -> s.getClassName() + ", " + s.getMethodName() + "() in " + s.getFileName() + ":" + s.getLineNumber()).toList();
        }

        private ExceptionDto(String message, Throwable throwable) {
            this.message = message;
            this.cause = "";
            this.at = throwable.getStackTrace()[0].getFileName() + ":" + throwable.getStackTrace()[0].getLineNumber();
            this.stackTrace = List.of();
        }
    }
}