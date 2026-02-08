package be.irail.api.gtfs.reader;

import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import jakarta.annotation.PostConstruct;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

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
        GtfsReader.GtfsData gtfsData = gtfsReader.readGtfs();
        GtfsInMemoryDao gtfsInMemoryDao = new GtfsInMemoryDao(gtfsData);
        GtfsInMemoryDao.setInstance(gtfsInMemoryDao);
        log.info("Gtfs data updated");
    }
}
