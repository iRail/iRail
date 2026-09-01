package be.irail.api.controllers.v1;

import be.irail.api.db.LogEntry;
import be.irail.api.db.LogQueryType;
import be.irail.api.db.RecentQueryLog;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Controller for V1 Logs API endpoint, reporting the queries handled over the last minute.
 */
@Component
@Path("/v1")
@Produces({MediaType.APPLICATION_JSON, MediaType.APPLICATION_XML})
public class LogsV1Controller {

    /**
     * Gets the queries this instance handled over the last minute, newest first.
     *
     * <p>Only recent queries are available: they are held in memory rather than stored, so there is
     * no history to page through. See {@link RecentQueryLog} for why.
     *
     * @return the recent queries as JSON
     */
    @GET
    @Path("/logs")
    public Response getLogs() {
        return Response.ok(toV1(RecentQueryLog.getInstance().getRecent())).build();
    }

    /**
     * Gets API usage logs for a specific date.
     *
     * <p>Kept so the documented route does not start returning 404, but this instance holds no
     * history — only the last minute, which {@code /v1/logs} serves. Always an empty list.
     *
     * @param date the date in yyyyMMdd format
     * @return an empty list
     */
    @GET
    @Path("/logs/{date}")
    public Response getLogsForDate(@PathParam("date") String date) {
        return Response.ok(List.of()).build();
    }

    /**
     * Converts entries to the shape the PHP implementation served, so existing consumers of this
     * endpoint keep working.
     *
     * @param entries the entries to convert, newest first
     * @return the entries as V1 maps
     */
    private static List<Map<String, Object>> toV1(List<LogEntry> entries) {
        List<Map<String, Object>> result = new ArrayList<>(entries.size());
        for (LogEntry entry : entries) {
            Map<String, Object> query = new LinkedHashMap<>(entry.getQuery());
            if (entry.getResult() != null) {
                query.putAll(entry.getResult());
            }
            Map<String, Object> converted = new LinkedHashMap<>();
            converted.put("querytype", v1Name(entry.getQueryType()));
            converted.put("querytime", entry.getCreatedAt().toString());
            converted.put("query", query);
            converted.put("user_agent", entry.getUserAgent());
            result.add(converted);
        }
        return result;
    }

    /**
     * The name this query type is published under.
     *
     * <p>Three of these differ from {@link LogQueryType#getValue()}, which carries the upstream
     * NMBS wording: the V1 API has always published {@code Connections},
     * {@code VehicleInformation} and {@code Disturbances}. Using the enum value here would silently
     * rename them for every existing consumer.
     *
     * @param queryType the internal query type
     * @return the name used in the V1 response
     */
    static String v1Name(LogQueryType queryType) {
        return switch (queryType) {
            case LIVEBOARD -> "Liveboard";
            case JOURNEYPLANNING -> "Connections";
            case DATEDVEHICLEJOURNEY -> "VehicleInformation";
            case VEHICLECOMPOSITION -> "Composition";
            case STATIONS -> "Stations";
            case SERVICEALERTS -> "Disturbances";
        };
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
