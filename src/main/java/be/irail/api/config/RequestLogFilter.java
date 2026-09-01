package be.irail.api.config;

import be.irail.api.db.LogQueryType;
import be.irail.api.db.RecentQueryLog;
import jakarta.ws.rs.container.ContainerRequestContext;
import jakarta.ws.rs.container.ContainerResponseContext;
import jakarta.ws.rs.container.ContainerResponseFilter;
import jakarta.ws.rs.core.HttpHeaders;
import jakarta.ws.rs.core.MultivaluedMap;
import jakarta.ws.rs.ext.Provider;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Records every handled API query in {@link RecentQueryLog}, so {@code /v1/logs} can report which
 * clients are asking for what. Runs after the response has been produced and never alters it.
 */
@Provider
public class RequestLogFilter implements ContainerResponseFilter {

    /** Maps the final path segment of a request onto the query type reported for it. */
    private static final Map<String, LogQueryType> QUERY_TYPE_BY_PATH = Map.of(
            "liveboard", LogQueryType.LIVEBOARD,
            "connections", LogQueryType.JOURNEYPLANNING,
            "journeyplanning", LogQueryType.JOURNEYPLANNING,
            "vehicle", LogQueryType.DATEDVEHICLEJOURNEY,
            "stations", LogQueryType.STATIONS,
            "composition", LogQueryType.VEHICLECOMPOSITION,
            "disturbances", LogQueryType.SERVICEALERTS,
            "servicealerts", LogQueryType.SERVICEALERTS
    );

    @Override
    public void filter(ContainerRequestContext requestContext, ContainerResponseContext responseContext) {
        LogQueryType queryType = queryTypeFor(requestContext.getUriInfo().getPath());
        if (queryType == null) {
            // Not a data query — the logs endpoint itself, redirects, health checks and feedback posts
            // are left out, both to avoid noise and so reading the logs cannot log itself.
            return;
        }
        RecentQueryLog.getInstance().record(
                queryType,
                toQueryMap(requestContext.getUriInfo().getQueryParameters()),
                requestContext.getHeaderString(HttpHeaders.USER_AGENT));
    }

    /**
     * Resolves the query type from a request path, ignoring the API version prefix.
     *
     * @param path the matched request path
     * @return the query type, or null when the path is not a data query
     */
    static LogQueryType queryTypeFor(String path) {
        if (path == null || path.isBlank()) {
            return null;
        }
        String trimmed = path.endsWith("/") ? path.substring(0, path.length() - 1) : path;
        int lastSegment = trimmed.lastIndexOf('/');
        String segment = lastSegment < 0 ? trimmed : trimmed.substring(lastSegment + 1);
        return QUERY_TYPE_BY_PATH.get(segment.toLowerCase());
    }

    /** Flattens the query parameters, keeping the first value of any repeated parameter. */
    private static Map<String, Object> toQueryMap(MultivaluedMap<String, String> queryParameters) {
        Map<String, Object> query = new LinkedHashMap<>();
        for (Map.Entry<String, List<String>> parameter : queryParameters.entrySet()) {
            if (!parameter.getValue().isEmpty()) {
                query.put(parameter.getKey(), parameter.getValue().getFirst());
            }
        }
        return query;
    }
}
