package be.irail.api.gtfs.reader;

import com.google.transit.realtime.GtfsRealtime.FeedMessage;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.io.InputStream;
import java.net.URI;
import java.net.URL;

import org.slf4j.LoggerFactory;

/**
 * Service for reading GTFS-Realtime data from a remote source.
 * This class handles the retrieval and parsing of TripUpdates in protobuf format.
 * The source URL is configurable via application properties or environment variables.
 */
@Service
public class GtfsRtReader {

    private static final Logger log = LogManager.getLogger(GtfsRtReader.class);

    @Value("${gtfs.rt.url:https://sncb-opendata.hafas.de/gtfs/realtime/d22ad6759ee25bg84ddb6c818g4dc4de_TC}")
    private String gtfsRtUrl;

    /**
     * Fetches and parses the latest TripUpdates from the configured GTFS-Realtime endpoint.
     *
     * @return the parsed FeedMessage containing TripUpdates, or null if an error occurred
     */
    public FeedMessage readTripUpdates() {
        log.info("Fetching GTFS-RT TripUpdates from {}", gtfsRtUrl);
        try {
            URL url = URI.create(gtfsRtUrl).toURL();
            try (InputStream inputStream = url.openStream()) {
                return FeedMessage.parseFrom(inputStream);
            }
        } catch (IOException e) {
            log.error("Failed to read or parse GTFS-RT feed from " + gtfsRtUrl, e);
            return null;
        } catch (IllegalArgumentException e) {
            log.error("Invalid GTFS-RT URL: " + gtfsRtUrl, e);
            return null;
        }
    }
}
