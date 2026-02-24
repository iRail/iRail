package be.irail.api.controllers.v1;

import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.util.Collections;
import java.util.Map;

/**
 * Controller for V1 Logs API endpoint.
 * Note: Full logging infrastructure (LogDao) is not yet implemented.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class LogsV1Controller {

    /**
     * Gets API usage logs (last 1000 entries).
     *
     * @return the logs as JSON
     */
    @GET
    @Path("/logs")
    public Response getLogs() {
        // TODO: Implement when LogDao is available
        return Response.ok("[]", "application/json;charset=UTF-8").build();
    }

    /**
     * Gets API usage logs for a specific date.
     *
     * @param date the date in yyyyMMdd format
     * @return the logs as JSON
     */
    @GET
    @Path("/logs/{date}")
    public Response getLogsForDate(@PathParam("date") String date) {
        // TODO: Implement when LogDao is available
        return Response.ok("[]", "application/json;charset=UTF-8").build();
    }

    /**
     * Stores occupancy data reported by users.
     *
     * @param body the occupancy report data
     * @return the response
     */
    @POST
    @Path("/occupancy")
    @Consumes(MediaType.APPLICATION_JSON)
    public Response storeOccupancy(Map<String, Object> body) {
        // TODO: Implement occupancy storage endpoint
        return Response.ok().build();
    }
}
