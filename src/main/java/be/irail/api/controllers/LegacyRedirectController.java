package be.irail.api.controllers;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.core.Context;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.net.URI;

@Component
@Path("/")
public class LegacyRedirectController {

    @Context
    private HttpServletRequest request;

    @GET
    @Path("/stations")
    public Response redirectStations() {
        return Response.seeOther(URI.create("https://" + request.getServerName() + "/v1/stations?" + request.getQueryString())).build();
    }

    @GET
    @Path("/liveboard")
    public Response redirectLiveboard() {
        return Response.seeOther(URI.create("https://" + request.getServerName() + "/v1/liveboard?" + request.getQueryString())).build();
    }

    @GET
    @Path("/connections")
    public Response redirectConnections() {
        return Response.seeOther(URI.create("https://" + request.getServerName() + "/v1/connections?" + request.getQueryString())).build();
    }

    @GET
    @Path("/vehicle")
    public Response redirectVehicle() {
        return Response.seeOther(URI.create("https://" + request.getServerName() + "/v1/vehicle?" + request.getQueryString())).build();
    }

    @GET
    @Path("/composition")
    public Response redirectComposition() {
        return Response.status(Response.Status.BAD_REQUEST).entity(
                "The iRail API uses prefixes to indicate versions, and has been returning redirects for a while now. "
                        + "Please migrate your application to use /v1/ in front of the legacy endpoints to avoid unnecessary redirects. "
                        + "The composition endpoint is only available through the updated URL: "
                        + URI.create("https://" + request.getRemoteHost() + "/v1/composition?" + request.getQueryString())).build();
    }
}
