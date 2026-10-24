package be.irail.api.controllers;

import be.irail.api.linkedconnections.LinkedConnectionsFeedService;
import be.irail.api.linkedconnections.LinkedConnectionsService;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.OPTIONS;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.*;
import org.springframework.stereotype.Component;

import java.net.URI;
import java.time.Instant;

/** Publishes recent Linked Connections changes as a one-page LDES 1.0 feed. */
@Component
@Path("/v1/feed")
public class LinkedConnectionsFeedController {
    private static final String ALLOW_ORIGIN = "Access-Control-Allow-Origin";
    private static final String ALLOW_METHODS = "Access-Control-Allow-Methods";
    private static final String ALLOW_HEADERS = "Access-Control-Allow-Headers";
    private static final String EXPOSE_HEADERS = "Access-Control-Expose-Headers";
    private static final String MAX_AGE = "Access-Control-Max-Age";
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

    @OPTIONS
    public Response options() {
        return addCorsHeaders(Response.noContent()).build();
    }

    private Response.ResponseBuilder addHeaders(Response.ResponseBuilder response, EntityTag entityTag) {
        CacheControl cacheControl = new CacheControl();
        cacheControl.setMaxAge(15);
        cacheControl.setPrivate(false);
        return addCorsHeaders(response.cacheControl(cacheControl)
                .tag(entityTag)
                .header(EXPOSE_HEADERS, HttpHeaders.ETAG));
    }

    private Response.ResponseBuilder addCorsHeaders(Response.ResponseBuilder response) {
        return response
                .header(ALLOW_ORIGIN, "*")
                .header(ALLOW_METHODS, "GET, OPTIONS")
                .header(ALLOW_HEADERS, "Accept, If-None-Match")
                .header(MAX_AGE, "86400")
                .header(HttpHeaders.VARY, HttpHeaders.ACCEPT);
    }
}
