package be.irail.api.controllers;

import be.irail.api.linkedconnections.LinkedConnectionsFeedService;
import jakarta.ws.rs.core.EntityTag;
import jakarta.ws.rs.core.Request;
import jakarta.ws.rs.core.Response;
import jakarta.ws.rs.core.UriInfo;
import org.junit.jupiter.api.Test;

import java.net.URI;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.when;

class LinkedConnectionsFeedControllerTest {
    @Test
    void includesCorsHeadersOnTheFeed() {
        LinkedConnectionsFeedService service = mock(LinkedConnectionsFeedService.class);
        UriInfo uriInfo = mock(UriInfo.class);
        Request request = mock(Request.class);
        when(uriInfo.getAbsolutePath()).thenReturn(URI.create("https://api.irail.be/v1/feed"));
        when(service.createFeed(any(), any())).thenReturn("{}");
        when(request.evaluatePreconditions(any(EntityTag.class))).thenReturn(null);

        try (Response response = new LinkedConnectionsFeedController(service).getFeed(uriInfo, request)) {
            assertCorsHeaders(response);
            assertEquals("ETag", response.getHeaderString("Access-Control-Expose-Headers"));
        }
    }

    @Test
    void supportsCorsPreflightRequests() {
        try (Response response = new LinkedConnectionsFeedController(mock(LinkedConnectionsFeedService.class))
                .options()) {
            assertEquals(Response.Status.NO_CONTENT.getStatusCode(), response.getStatus());
            assertCorsHeaders(response);
        }
    }

    @Test
    void includesCorsHeadersOnNotModifiedResponses() {
        LinkedConnectionsFeedService service = mock(LinkedConnectionsFeedService.class);
        UriInfo uriInfo = mock(UriInfo.class);
        Request request = mock(Request.class);
        when(uriInfo.getAbsolutePath()).thenReturn(URI.create("https://api.irail.be/v1/feed"));
        when(service.createFeed(any(), any())).thenReturn("{}");
        when(request.evaluatePreconditions(any(EntityTag.class))).thenReturn(Response.notModified());

        try (Response response = new LinkedConnectionsFeedController(service).getFeed(uriInfo, request)) {
            assertEquals(Response.Status.NOT_MODIFIED.getStatusCode(), response.getStatus());
            assertCorsHeaders(response);
            assertEquals("ETag", response.getHeaderString("Access-Control-Expose-Headers"));
        }
    }

    private void assertCorsHeaders(Response response) {
        assertEquals("*", response.getHeaderString("Access-Control-Allow-Origin"));
        assertEquals("GET, OPTIONS", response.getHeaderString("Access-Control-Allow-Methods"));
        assertEquals("Accept, If-None-Match", response.getHeaderString("Access-Control-Allow-Headers"));
        assertEquals("86400", response.getHeaderString("Access-Control-Max-Age"));
    }
}
