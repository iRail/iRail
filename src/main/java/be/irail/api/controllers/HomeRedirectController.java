package be.irail.api.controllers;

import be.irail.api.config.Metrics;
import com.codahale.metrics.Meter;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.core.Response;
import org.springframework.stereotype.Component;

import java.net.URI;

@Component
@Path("/")
public class HomeRedirectController {

    private final Meter redirectMeter = Metrics.getRegistry().meter("root-docs-redirect");

    @GET
    @Path("/")
    public Response redirectStations() {
        redirectMeter.mark();
        return Response.seeOther(URI.create("https://docs.irail.be")).build();
    }

}
