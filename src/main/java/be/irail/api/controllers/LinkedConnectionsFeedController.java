package be.irail.api.controllers;

import be.irail.api.linkedconnections.LinkedConnectionsFeedService;
import be.irail.api.linkedconnections.LinkedConnectionsService;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.*;
import org.springframework.stereotype.Component;

import java.net.URI;
import java.time.Instant;

/** Publishes recent Linked Connections changes as a one-page LDES 1.0 feed. */
@Component
@Path("/1.0/feed")
public class LinkedConnectionsFeedController {
    private static final String CORS_HEADER = "Access-Control-Allow-Origin";
    private final LinkedConnectionsFeedService service;

    public LinkedConnectionsFeedController(LinkedConnectionsFeedService service) {
        this.service = service;
    }

    @GET
    @Produces(LinkedConnectionsService.JSON_LD_MEDIA_TYPE)
    public Response getFeed(@Context UriInfo uriInfo, @Context Request request) {
        URI feedUri = uriInfo.getAbsolutePath();
        String jsonLd = service.createFeed(feedUri, Instant.now());
        EntityTag entityTag = new EntityTag(Integer.toHexString(jsonLd.hashCode()), true);
        Response.ResponseBuilder preconditionResponse = request.evaluatePreconditions(entityTag);
        if (preconditionResponse != null) {
            return addHeaders(preconditionResponse, entityTag).build();
        }
        return addHeaders(Response.ok(jsonLd, LinkedConnectionsService.JSON_LD_MEDIA_TYPE), entityTag).build();
    }

    private Response.ResponseBuilder addHeaders(Response.ResponseBuilder response, EntityTag entityTag) {
        CacheControl cacheControl = new CacheControl();
        cacheControl.setMaxAge(15);
        cacheControl.setPrivate(false);
        return response.cacheControl(cacheControl)
                .tag(entityTag)
                .header(CORS_HEADER, "*")
                .header(HttpHeaders.VARY, HttpHeaders.ACCEPT);
    }
}
