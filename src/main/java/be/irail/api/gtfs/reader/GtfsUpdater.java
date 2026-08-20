package be.irail.api.gtfs.reader;

import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.exception.InternalProcessingException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.gtfs.reader.models.Stop;
import jakarta.annotation.PostConstruct;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.jspecify.annotations.Nullable;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.time.Duration;
import java.util.List;
import java.util.stream.Collectors;

/**
 * Service that periodically updates GTFS-Realtime data.
 */
@Service
public class GtfsUpdater {

    private static final Logger log = LogManager.getLogger(GtfsUpdater.class);
    private final GtfsReader gtfsReader;
    private final StationsDao stationsDao;

    public GtfsUpdater(GtfsReader gtfsReader, StationsDao stationsDao) {
        this.gtfsReader = gtfsReader;
        this.stationsDao = stationsDao;
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

        addMissingStationsToStationsDao(gtfsInMemoryDao);
        logFailingStationNameLookups(gtfsInMemoryDao);

        log.info("Gtfs data updated");
    }

    private void addMissingStationsToStationsDao(GtfsInMemoryDao gtfsInMemoryDao) {
        int added = 0;
        for (Stop stop : gtfsInMemoryDao.getAllStations()) {
            String hafasId = Stop.getHafasId(stop.id());
            if (hafasId == null) {
                log.debug("Not checking if GTFS station with id {} exists in StationsDao, could not convert id to HAFAS id", stop.id());
                continue;
            }
            if (stationsDao.getStationFromId(hafasId) == null) {
                Station station = Station.fromGtfsStop(stop);
                log.info("Adding missing station {}, not found by id lookup for {}", station.getIrailId(), hafasId);
                stationsDao.memorizeExternalStop(station);
                added++;
            }
        }
        if (added > 0) {
            log.warn("Added {} missing stations to StationsDao", added);
        }
    }

    private void logFailingStationNameLookups(GtfsInMemoryDao gtfsInMemoryDao) {
        for (Stop stop : gtfsInMemoryDao.getAllStations()) {
            String hafasId = Stop.getHafasId(stop.id());
            if (hafasId == null) {
                log.debug("Not checking if GTFS station with name {} exists in StationsDao, could not convert id {} to HAFAS id",
                        stop.name(), stop.id());
                continue;
            }
            List<Station> queryResult = stationsDao.getStations(stop.name());
            // This is performed after memorizing missing GTFS stations, so this would mean the database name differs
            // from the GTFS name.
            if (queryResult == null) {
                log.error("Failed to find GTFS station for with name {} in StationsDao", stop.name());
                continue;
            }
            if (queryResult.stream().noneMatch(station -> station.getHafasId().equals(hafasId))) {
                log.error("Failed to find GTFS station for with name {} in StationsDao, found {} but not {}",
                        stop.name(),
                        queryResult.stream().map(Station::getHafasId).collect(Collectors.joining(", ")),
                        hafasId);
            }
        }
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
