package be.irail.api.db;

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

    private static Map<JourneyKey, List<OccupancyReport>> reportsByJourney;
    private static Map<StationKey, List<OccupancyReport>> reportsByStation;
    private static Map<StationJourneyAndSourceKey, OccupancyReport> reportsByStationJourneyAndStartDate;

    private static final List<OccupancyReport> pendingUpdates = new ArrayList<>();

    private record JourneyKey(String journeyId, LocalDate startDate) {
    }

    private record StationKey(Integer stopId, LocalDate startDate) {
    }

    private record StationJourneyAndSourceKey(Integer stopId, String journeyId, LocalDate startDate,
                                              OccupancyReport.OccupancyReportSource source) {
    }

    /**
     * Returns all occupancy reports for a given journey.
     *
     * @param journeyId the journey number (e.g. IC548)
     * @param startDate the start date of the journey
     * @return a list of occupancy reports
     */
    public List<OccupancyReport> getReportsForJourney(String journeyId, LocalDate startDate) {
        initializeOccupancy();
        return reportsByJourney.getOrDefault(new JourneyKey(journeyId, startDate), new ArrayList<>());
    }

    /**
     * Returns all occupancy reports for a given station on a specific date.
     *
     * @param stationId the station ID (e.g. 008812005)
     * @param startDate the date of the report
     * @return a list of occupancy reports
     */
    public List<OccupancyReport> getReportsForStation(String stationId, LocalDate startDate) {
        initializeOccupancy();
        // The table has stop_id as integer. stationId might be a URI or a numeric string.
        Integer stopId = extractStopId(stationId);
        return reportsByStation.getOrDefault(new StationKey(stopId, startDate), new ArrayList<>());
    }

    private Integer extractStopId(String stationId) {
        if (stationId == null) {
            return null;
        }
        if (stationId.startsWith("http://irail.be/stations/NMBS/")) {
            stationId = stationId.substring(30);
        }
        try {
            return Integer.parseInt(stationId.replaceAll("\\D+", ""));
        } catch (NumberFormatException e) {
            return null;
        }
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
            List<OccupancyReport> journeyReports = reportsByJourney.computeIfAbsent(journeyKey, k -> new ArrayList<>());
            updateListCache(journeyReports, report);

            // Update station cache
            StationKey stationKey = new StationKey(report.getStopId(), report.getJourneyStartDate());
            List<OccupancyReport> stationReports = reportsByStation.computeIfAbsent(stationKey, k -> new ArrayList<>());
            updateListCache(stationReports, report);

            synchronized (pendingUpdates) {
                pendingUpdates.add(report);
            }
        }
    }

    private void updateListCache(List<OccupancyReport> reports, OccupancyReport newReport) {
        // Remove existing report for the same vehicle and stop if it exists
        reports.removeIf(r -> r.getStopId().equals(newReport.getStopId())
                && r.getVehicleId().equals(newReport.getVehicleId())
                && r.getSource().equals(newReport.getSource()));
        reports.add(newReport);
    }

    /**
     * Reads all occupancy reports from the database and initializes in-memory maps.
     */
    private synchronized void initializeOccupancy() {
        if (reportsByJourney != null) {
            return;
        }

        LocalDate earliestDateToLoad = LocalDate.now().minusDays(1);
        List<OccupancyReport> allReports = entityManager.createQuery("SELECT r FROM OccupancyReport r WHERE r.journeyStartDate >= :earliestDate", OccupancyReport.class)
                .setParameter("earliestDate", earliestDateToLoad)
                .getResultList();

        Map<JourneyKey, List<OccupancyReport>> journeyMap = new HashMap<>();
        Map<StationKey, List<OccupancyReport>> stationMap = new HashMap<>();
        Map<StationJourneyAndSourceKey, OccupancyReport> specificMap = new HashMap<>();

        for (OccupancyReport report : allReports) {
            // Specific key
            StationJourneyAndSourceKey specificKey = new StationJourneyAndSourceKey(
                    report.getStopId(), report.getVehicleId(),
                    report.getJourneyStartDate(), report.getSource());
            specificMap.put(specificKey, report);

            // Journey key
            JourneyKey journeyKey = new JourneyKey(report.getVehicleId(), report.getJourneyStartDate());
            journeyMap.computeIfAbsent(journeyKey, k -> new ArrayList<>()).add(report);

            // Station key
            StationKey stationKey = new StationKey(report.getStopId(), report.getJourneyStartDate());
            stationMap.computeIfAbsent(stationKey, k -> new ArrayList<>()).add(report);
        }

        reportsByJourney = journeyMap;
        reportsByStation = stationMap;
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
