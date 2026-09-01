package be.irail.api.controllers;

import be.irail.api.exception.request.BadRequestException;
import be.irail.api.linkedconnections.LinkedConnectionsService;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.QueryParam;
import jakarta.ws.rs.core.*;
import org.springframework.stereotype.Component;

import java.net.URI;
import java.time.Instant;
import java.time.OffsetDateTime;
import java.time.format.DateTimeParseException;

/** Publishes the NMBS/SNCB timetable as Linked Connections 1.0 fragments. */
@Component
@Path("/graph")
public class LinkedConnectionsController {
    private static final String CORS_HEADER = "Access-Control-Allow-Origin";
    private final LinkedConnectionsService service;

    public LinkedConnectionsController(LinkedConnectionsService service) {
        this.service = service;
    }

    @GET
    @Produces(LinkedConnectionsService.JSON_LD_MEDIA_TYPE)
    public Response getGraph(
            @QueryParam("departureTime") String departureTime,
            @Context UriInfo uriInfo,
            @Context Request request) {
        Instant requested = parseDepartureTime(departureTime);
        Instant pageStart = service.pageStart(requested);
        URI graphUri = uriInfo.getAbsolutePath();
        URI canonicalUri = URI.create(graphUri + "?departureTime=" + service.formatInstant(pageStart));

        if (departureTime == null || !departureTime.equals(service.formatInstant(pageStart))) {
            return Response.temporaryRedirect(canonicalUri)
                    .header(CORS_HEADER, "*")
                    .build();
        }

        String jsonLd = service.createPage(graphUri, pageStart);
        EntityTag entityTag = new EntityTag(Integer.toHexString(jsonLd.hashCode()), true);
        Response.ResponseBuilder preconditionResponse = request.evaluatePreconditions(entityTag);
        if (preconditionResponse != null) {
            return addHeaders(preconditionResponse, entityTag).build();
        }
        return addHeaders(Response.ok(jsonLd, LinkedConnectionsService.JSON_LD_MEDIA_TYPE), entityTag).build();
    }

    private Instant parseDepartureTime(String departureTime) {
        if (departureTime == null) {
            return Instant.now();
        }
        try {
            return OffsetDateTime.parse(departureTime).toInstant();
        } catch (DateTimeParseException exception) {
            throw new BadRequestException("Expected an ISO 8601 date-time with a UTC offset", "departureTime", departureTime);
        }
    }

    private Response.ResponseBuilder addHeaders(Response.ResponseBuilder response, EntityTag entityTag) {
        CacheControl cacheControl = new CacheControl();
        cacheControl.setMaxAge(30);
        cacheControl.setPrivate(false);
        return response.cacheControl(cacheControl)
                .tag(entityTag)
                .header(CORS_HEADER, "*")
                .header(HttpHeaders.VARY, HttpHeaders.ACCEPT);
    }
}