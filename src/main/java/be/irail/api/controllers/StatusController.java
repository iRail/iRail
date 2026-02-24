package be.irail.api.controllers;

import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

/**
 * Controller for health and cache status endpoints.
 * Provides system monitoring and maintenance operations using JAX-RS.
 */
@Component
@Path("/")
public class StatusController {

    /**
     * Returns the health status of the application.
     *
     * @return a health status report
     */
    @GET
    @Path("/health")
    @Produces(MediaType.TEXT_HTML)
    public Response showStatus() {
        return Response.ok("OK").build();
    }

}
