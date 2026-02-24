package be.irail.api.controllers;

import be.irail.api.dto.Language;
import be.irail.api.dto.TimeSelection;
import be.irail.api.util.RequestParser;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.util.Map;

/**
 * Controller for V2 API endpoints.
 * Handles modern iRail API requests using JAX-RS / Jersey.
 */
@Component
@Path("/v2")
@Produces(MediaType.APPLICATION_JSON)
public class V2Controller {

    /**
     * Gets the liveboard for a specific station with mandatory mode and ID.
     *
     * @param departureArrivalMode arrival or departure
     * @param id station ID
     * @param datetime optional datetime for the liveboard
     * @param lang language for response data
     * @return the liveboard search result
     */
    @GET
    @Path("/liveboard/{departureArrivalMode}/{id}")
    public Response getLiveboard(
            @PathParam("departureArrivalMode") String departureArrivalMode,
            @PathParam("id") String id,
            @QueryParam("datetime") String datetime,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        TimeSelection mode = RequestParser.parseV2TimeSelection(departureArrivalMode);
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets journey planning between two stations.
     *
     * @param from departure station
     * @param to arrival station
     * @param datetime optional datetime
     * @param arrdep arrival or departure selection
     * @param lang language
     * @return journey planning result
     */
    @GET
    @Path("/journeyplanning/{from}/{to}")
    public Response getJourneyPlanning(
            @PathParam("from") String from,
            @PathParam("to") String to,
            @QueryParam("datetime") String datetime,
            @QueryParam("arrdep") String arrdep,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        TimeSelection mode = (arrdep != null) ? RequestParser.parseV2TimeSelection(arrdep) : TimeSelection.DEPARTURE;
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets journey planning between two stations with specific time.
     *
     * @param from departure station
     * @param to arrival station
     * @param arrdep arrival or departure selection
     * @param datetime datetime of travel
     * @param lang language
     * @return journey planning result
     */
    @GET
    @Path("/journeyplanning/{from}/{to}/{arrdep}/{datetime}")
    public Response getJourneyPlanningWithTime(
            @PathParam("from") String from,
            @PathParam("to") String to,
            @PathParam("arrdep") String arrdep,
            @PathParam("datetime") String datetime,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        TimeSelection mode = RequestParser.parseV2TimeSelection(arrdep);
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets information about a vehicle.
     *
     * @param id vehicle ID
     * @param datetime optional datetime
     * @param lang language
     * @return vehicle journey result
     */
    @GET
    @Path("/vehicle/{id}")
    public Response getVehicle(
            @PathParam("id") String id,
            @QueryParam("datetime") String datetime,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets information about a vehicle at a specific time.
     *
     * @param id vehicle ID
     * @param datetime datetime
     * @param lang language
     * @return vehicle journey result
     */
    @GET
    @Path("/vehicle/{id}/{datetime}")
    public Response getVehicleWithTime(
            @PathParam("id") String id,
            @PathParam("datetime") String datetime,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets service alerts.
     *
     * @param lang language
     * @return service alerts result
     */
    @GET
    @Path("/servicealerts")
    public Response getServiceAlerts(
            @QueryParam("lang") @DefaultValue("en") String lang) {
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets vehicle composition.
     *
     * @param id vehicle ID
     * @param lang language
     * @return composition result
     */
    @GET
    @Path("/composition")
    public Response getComposition(
            @QueryParam("id") String id,
            @QueryParam("lang") @DefaultValue("en") String lang) {
        Language l = RequestParser.parseLanguage(lang);
        return Response.ok().build();
    }

    /**
     * Gets all logs.
     *
     * @return logs
     */
    @GET
    @Path("/logs")
    public Response getLogs() {
        return Response.ok().build();
    }

    /**
     * Gets logs for a specific date.
     *
     * @param date date string
     * @return logs for that date
     */
    @GET
    @Path("/logs/{date}")
    public Response getLogsForDate(@PathParam("date") String date) {
        return Response.ok().build();
    }

    /**
     * Stores occupancy feedback.
     *
     * @param body occupancy data
     * @return success or error response
     */
    @POST
    @Path("/feedback/occupancy")
    @Consumes(MediaType.APPLICATION_JSON)
    public Response storeOccupancy(Map<String, Object> body) {
        return Response.ok().build();
    }

    /**
     * Dumps occupancy reports.
     *
     * @return reports dump
     */
    @GET
    @Path("/feedback/reports")
    public Response dumpOccupancy() {
        return Response.ok().build();
    }
}
