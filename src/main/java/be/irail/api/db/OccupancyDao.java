package be.irail.api.db;

import be.irail.api.dto.DepartureOrArrival;
import be.irail.api.dto.OccupancyInfo;
import com.google.common.collect.ArrayListMultimap;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Dao for accessing occupancy reports, with in-memory caching.
 */
@Repository
public class OccupancyDao {

    @PersistenceContext
    private EntityManager entityManager;

    private static Map<JourneyKey, ArrayListMultimap<Integer, OccupancyReport>> reportsByJourneyAndStation;
    private static Map<StationJourneyAndSourceKey, OccupancyReport> reportsByStationJourneyAndStartDate;

    private static final List<OccupancyReport> pendingUpdates = new ArrayList<>();

    private record JourneyKey(String journeyId, LocalDate startDate) {
    }

    private record StationJourneyAndSourceKey(Integer stopId, String journeyId, LocalDate startDate,
                                              OccupancyReport.OccupancyReportSource source) {
    }

    /**
     * Returns all occupancy reports for a given journey.
     *
     * @param journeyId the journey number (e.g. IC548)
     * @param startDate the start date of the journey
     * @return a multimap of stations and occupancy reports for them
     */
    public ArrayListMultimap<Integer, OccupancyReport> getOccupancy(String journeyId, LocalDate startDate) {
        initializeOccupancy();
        return reportsByJourneyAndStation.getOrDefault(new JourneyKey(journeyId, startDate), ArrayListMultimap.create());
    }

    public OccupancyInfo getOccupancy(DepartureOrArrival stop) {
        Integer stopId = extractNumericStopId(stop.getStation().getId());

        List<OccupancyReport> reports = getOccupancy(
                stop.getVehicle().getId(),
                stop.getScheduledDateTime().toLocalDate()
        ).get(stopId);

        OccupancyReport.OccupancyLevel official = null;
        OccupancyReport.OccupancyLevel spitsgids = null;

        for (OccupancyReport report : reports) {
            if (report.getStopId().equals(stopId)) {
                if (report.getSource() == OccupancyReport.OccupancyReportSource.NMBS) {
                    official = report.getOccupancy();
                } else if (report.getSource() == OccupancyReport.OccupancyReportSource.SPITSGIDS) {
                    spitsgids = report.getOccupancy();
                }
            }
        }
        return new OccupancyInfo(official, spitsgids);
    }

    /**
     * Handles a new report, updating caches and tracking pending updates if it's new or changed.
     */
    public synchronized void handleReport(OccupancyReport report) {
        initializeOccupancy();

        StationJourneyAndSourceKey specificKey = new StationJourneyAndSourceKey(
                report.getStopId(), report.getVehicleId(),
                report.getJourneyStartDate(), report.getSource()
        );
        OccupancyReport existing = reportsByStationJourneyAndStartDate.get(specificKey);

        if (existing == null || !existing.getOccupancy().equals(report.getOccupancy()) || !existing.getSource().equals(report.getSource())) {
            // Update specific cache
            reportsByStationJourneyAndStartDate.put(specificKey, report);

            // Update journey cache
            JourneyKey journeyKey = new JourneyKey(report.getVehicleId(), report.getJourneyStartDate());
            ArrayListMultimap<Integer, OccupancyReport> journeyReports =
                    reportsByJourneyAndStation.computeIfAbsent(journeyKey, k -> ArrayListMultimap.create());
            updateReportsByStationCache(journeyReports, report);

            synchronized (pendingUpdates) {
                pendingUpdates.add(report);
            }
        }
    }

    private Integer extractNumericStopId(String stationId) {
        if (stationId == null) {
            return null;
        }
        try {
            return Integer.parseInt(stationId.replaceAll("\\D+", ""));
        } catch (NumberFormatException e) {
            return null;
        }
    }

    private void updateReportsByStationCache(ArrayListMultimap<Integer, OccupancyReport> reports, OccupancyReport newReport) {
        // Remove existing report for the same vehicle and stop if it exists
        reports.get(newReport.getStopId()).removeIf(r -> r.getStopId().equals(newReport.getStopId())
                && r.getVehicleId().equals(newReport.getVehicleId())
                && r.getSource().equals(newReport.getSource()));
        reports.put(newReport.getStopId(), newReport);
    }

    /*
     * Reads all occupancy reports from the database and initializes in-memory maps.
     */
    private synchronized void initializeOccupancy() {
        if (reportsByJourneyAndStation != null) {
            return;
        }

        LocalDate earliestDateToLoad = LocalDate.now().minusDays(1);
        List<OccupancyReport> allReports = entityManager.createQuery("SELECT r FROM OccupancyReport r WHERE r.journeyStartDate >= :earliestDate", OccupancyReport.class)
                .setParameter("earliestDate", earliestDateToLoad)
                .getResultList();

        Map<JourneyKey, ArrayListMultimap<Integer, OccupancyReport>> journeyMap = new HashMap<>();
        Map<StationJourneyAndSourceKey, OccupancyReport> specificMap = new HashMap<>();

        for (OccupancyReport report : allReports) {
            // Specific key
            StationJourneyAndSourceKey specificKey = new StationJourneyAndSourceKey(
                    report.getStopId(), report.getVehicleId(),
                    report.getJourneyStartDate(), report.getSource());
            specificMap.put(specificKey, report);

            // Journey key
            JourneyKey journeyKey = new JourneyKey(report.getVehicleId(), report.getJourneyStartDate());
            journeyMap.computeIfAbsent(journeyKey, k -> ArrayListMultimap.create()).put(report.getStopId(), report);
        }

        reportsByJourneyAndStation = journeyMap;
        reportsByStationJourneyAndStartDate = specificMap;
    }

    /**
     * Flushes all pending occupancy reports to the database every minute.
     */
    @Scheduled(fixedRate = 60000)
    @Transactional
    public void flushPendingUpdates() {
        List<OccupancyReport> reportsToUpdate;
        synchronized (pendingUpdates) {
            if (pendingUpdates.isEmpty()) {
                return;
            }
            reportsToUpdate = new ArrayList<>(pendingUpdates);
            pendingUpdates.clear();
        }

        for (OccupancyReport report : reportsToUpdate) {
            entityManager.merge(report);
        }
        entityManager.flush();
    }
}
