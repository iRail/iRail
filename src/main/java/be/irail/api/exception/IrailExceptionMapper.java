package be.irail.api.exception;

import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.ext.ExceptionMapper;
import jakarta.ws.rs.ext.Provider;

@Provider
public class IrailExceptionMapper implements ExceptionMapper<IrailHttpException> {

    @Override
    public Response toResponse(IrailHttpException exception) {
        return Response.status(exception.getHttpCode()).entity(exception).build();
    }

}