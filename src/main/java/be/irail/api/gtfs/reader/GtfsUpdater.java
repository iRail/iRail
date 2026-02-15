package be.irail.api.gtfs.reader;

import be.irail.api.exception.InternalProcessingException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import jakarta.annotation.PostConstruct;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.jspecify.annotations.Nullable;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.time.Duration;

/**
 * Service that periodically updates GTFS-Realtime data.
 */
@Service
public class GtfsUpdater {

    private static final Logger log = LogManager.getLogger(GtfsUpdater.class);
    private final GtfsReader gtfsReader;

    public GtfsUpdater(GtfsReader gtfsReader) {
        this.gtfsReader = gtfsReader;
    }

    /**
     * Periodically fetches and processes the GTFS schedule
     * Runs daily at 10:00
     */
    @PostConstruct
    @Scheduled(cron = "0 0 10 * * *")
    public void update() {
        log.info("Updating gtfs data");
        GtfsReader.GtfsData gtfsData = readGtfsDataWithRetry();
        if (gtfsData == null) {
            return;
        }
        GtfsInMemoryDao gtfsInMemoryDao = new GtfsInMemoryDao(gtfsData);
        GtfsInMemoryDao.setInstance(gtfsInMemoryDao);
        log.info("Gtfs data updated");
    }

    private GtfsReader.@Nullable GtfsData readGtfsDataWithRetry() {
        GtfsReader.GtfsData gtfsData = null;
        try {
            gtfsData = gtfsReader.readGtfs();
        } catch (IOException e) {
            log.error("Failed to read GTFS data, retrying...", e);
            try {
                Thread.sleep(Duration.ofSeconds(5));
            } catch (InterruptedException ex) {
                // ignored
            }
            try {
                gtfsData = gtfsReader.readGtfs();
            } catch (IOException ex) {
                log.error("Failed to read GTFS data", ex);
                // If no data has been loaded previously, throw an exception
                if (GtfsInMemoryDao.getInstance() == null) {
                    throw new InternalProcessingException("Failed to load initial GTFS data");
                }
                // If data has been loaded already, just continue running the application.
                return null;
            }
        }
        return gtfsData;
    }
}
