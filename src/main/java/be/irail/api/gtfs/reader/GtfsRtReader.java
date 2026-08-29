package be.irail.api.gtfs.reader;

import com.google.transit.realtime.GtfsRealtime.FeedMessage;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.time.Instant;

/**
 * Service for reading GTFS-Realtime data from a remote source.
 * Handles retrieval and parsing of TripUpdates in protobuf format. The source URL and,
 * when the upstream requires it, an API key header are configurable via application
 * properties or environment variables. Belgian Mobility (an Azure API Management gateway)
 * returns "403 Quota Exceeded" for unauthenticated or over-quota callers; on a 403 or 429
 * the reader honours Retry-After and backs off instead of hammering the endpoint.
 */
@Service
public class GtfsRtReader {

    private static final Logger log = LogManager.getLogger(GtfsRtReader.class);

    /** Fallback back-off when the upstream throttles without a Retry-After header. */
    private static final long DEFAULT_BACKOFF_SECONDS = 300;

    private static final String USER_AGENT = "iRail-gtfs-rt";

    @Value("${gtfs.rt.url:https://sncb-opendata.hafas.de/gtfs/realtime/d22ad6759ee25bg84ddb6c818g4dc4de_TC}")
    private String gtfsRtUrl;

    /** Optional API key sent with each request; empty means unauthenticated (e.g. the hafas feed). */
    @Value("${gtfs.rt.apiKey:}")
    private String apiKey;

    /** Header the API key is sent in. Belgian Mobility uses bmc-partner-key; Azure APIM's generic default is Ocp-Apim-Subscription-Key. */
    @Value("${gtfs.rt.apiKeyHeader:bmc-partner-key}")
    private String apiKeyHeader;

    private final HttpClient httpClient = HttpClient.newBuilder()
            .followRedirects(HttpClient.Redirect.NORMAL)
            .connectTimeout(Duration.ofSeconds(10))
            .build();

    /** When throttled, do not fetch again before this instant. */
    private Instant backoffUntil;

    /**
     * Fetches and parses the latest TripUpdates from the configured GTFS-Realtime endpoint.
     *
     * @return the parsed FeedMessage, or null while backing off, when throttled, or on an error
     */
    public FeedMessage readTripUpdates() {
        FeedMessage feed = null;
        if (isBackingOff()) {
            log.debug("GTFS-RT feed backing off until {}, skipping fetch", backoffUntil);
        } else {
            log.info("Fetching GTFS-RT TripUpdates from {}", gtfsRtUrl);
            try {
                HttpResponse<byte[]> response = httpClient.send(buildRequest(), HttpResponse.BodyHandlers.ofByteArray());
                int status = response.statusCode();
                if (status == 200) {
                    feed = FeedMessage.parseFrom(response.body());
                } else if (status == 403 || status == 429) {
                    startBackoff(response);
                } else {
                    log.error("GTFS-RT feed at {} returned unexpected status {}", gtfsRtUrl, status);
                }
            } catch (IOException e) {
                log.error("Failed to read or parse GTFS-RT feed from {}", gtfsRtUrl, e);
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
                log.error("Interrupted while fetching GTFS-RT feed from {}", gtfsRtUrl, e);
            }
        }
        return feed;
    }

    private boolean isBackingOff() {
        return backoffUntil != null && Instant.now().isBefore(backoffUntil);
    }

    private HttpRequest buildRequest() {
        HttpRequest.Builder request = HttpRequest.newBuilder()
                .uri(URI.create(gtfsRtUrl))
                .timeout(Duration.ofSeconds(20))
                .header("User-Agent", USER_AGENT)
                .GET();
        if (apiKey != null && !apiKey.isBlank()) {
            request.header(apiKeyHeader, apiKey);
        }
        return request.build();
    }

    /** Starts a back-off window, honouring Retry-After (delta-seconds) when present. */
    private void startBackoff(HttpResponse<byte[]> response) {
        long seconds = response.headers().firstValue("Retry-After")
                .map(this::parseRetryAfterSeconds)
                .orElse(DEFAULT_BACKOFF_SECONDS);
        backoffUntil = Instant.now().plusSeconds(seconds);
        log.warn("GTFS-RT feed at {} returned HTTP {} (quota/throttle); backing off {}s until {}",
                gtfsRtUrl, response.statusCode(), seconds, backoffUntil);
    }

    private long parseRetryAfterSeconds(String value) {
        try {
            return Math.max(0, Long.parseLong(value.trim()));
        } catch (NumberFormatException e) {
            // Retry-After may also be an HTTP-date; fall back rather than parse it precisely.
            return DEFAULT_BACKOFF_SECONDS;
        }
    }
}
