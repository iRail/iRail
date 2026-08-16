package be.irail.api.db;

import be.irail.api.exception.InternalProcessingException;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.util.concurrent.UncheckedExecutionException;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Repository;

import java.util.*;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

/**
 * This Dao searches through the stations list (in memory, after reading from the database once) according to the
 * irail/stations php logic.
 */
@Repository
public class StationsDao {

    private static final Logger log = LoggerFactory.getLogger(StationsDao.class);
    private static final int STATION_SEARCH_RESULT_COUNT = 5;
    private final Cache<String, Optional<Station>> stationByIdCache = CacheBuilder.newBuilder()
            .maximumSize(3600)
            .expireAfterWrite(8, TimeUnit.HOURS)
            .build();
    private final Cache<String, List<Station>> stationByNameCache = CacheBuilder.newBuilder()
            .maximumSize(3600)
            .expireAfterWrite(4, TimeUnit.HOURS)
            .build();
    @PersistenceContext
    private EntityManager entityManager;
    private Map<String, Station> stationsById;
    private List<Station> stationsSortedBySize;

    /**
     * Gets you stations in a list ordered by relevance to the optional query.
     *
     * @param query The name or part of a name to search for
     * @return a list of stations
     */
    public List<Station> getStations(String query) {
        initializeStations();

        if (query == null || query.isEmpty()) {
            return new ArrayList<>(this.stationsSortedBySize);
        }

        try {
            return stationByNameCache.get(query, () -> {
                // Standardization and normalization
                String normalizedQuery = standardizeQuery(query);
                normalizedQuery = normalizeAccents(normalizedQuery);
                normalizedQuery = normalize(normalizedQuery);
                int count = 0;
                List<Station> resultStations = new ArrayList<>();

                for (Station station : this.stationsSortedBySize) {
                    StationMatchResult stationMatchResult = evaluateStationMatch(station, normalizedQuery);

                    if (stationMatchResult.exactMatch()) {
                        putInFirstPlace(resultStations, station);
                        count++;
                    } else if (stationMatchResult.partialMatch()) {
                        resultStations.add(station);
                        count++;
                    }

                    if (count > STATION_SEARCH_RESULT_COUNT) {
                        return resultStations;
                    }
                }

                log.trace("Found {} stations for query {}: {}", resultStations.size(), query, resultStations.stream().map(Station::getName).collect(Collectors.joining(", ")));
                return resultStations;
            });
        } catch (UncheckedExecutionException | ExecutionException e) {
            log.error("Failed to get stations for query {}", query, e);
            throw new InternalProcessingException(e);
        }
    }

    /**
     * Add a (temporary) station to the list of stations, in case a station discovered from the GTFS data or
     * API responses is missing in the database. Data is not persisted to the database to ensure data quality.
     * <p>
     * TODO: Store stations in a separate database table, with a way to accept or reject them. When accepted,
     *       their data should be completed.
     *
     * @param station The station to store
     */
    public synchronized void memorizeExternalStop(Station station) {
        initializeStations();
        if (stationsById.containsKey(station.getIrailId())) {
            log.error("Station {} already exists in database, not memorizing it", station.getName());
        }
        // Stops are only loaded at application startup, so in practice temporary stops will be memorized until a restart
        log.warn("Adding station {} to stations list, will be forgotten on next reload from database", station.getName());
        stationsSortedBySize.add(station);
        stationsById.put(station.getIrailId(), station);
        // Reset caches to ensure the new station is found in method calls after this one
        log.warn("Invalidating caches after adding station {}", station.getName());
        stationByIdCache.invalidateAll();
        stationByNameCache.invalidateAll();
    }

    private StationsDao.StationMatchResult evaluateStationMatch(Station station, String normalizedQuery) {
        boolean exactMatch = false;
        boolean partialMatch = false;

        List<String> allNames = new ArrayList<>();
        allNames.add(station.getName());
        allNames.addAll(station.getLocalizedNames());
        // unique names
        allNames = allNames.stream().distinct().collect(Collectors.toList());

        for (String name : allNames) {
            String testStationName = normalize(normalizeAccents(name));
            testStationName = testStationName.replaceAll("([- ])+", " ");

            if (isEqualCaseInsensitive(normalizedQuery, testStationName)) {
                exactMatch = true;
                break;
            }

            if (isQueryPartOfName(normalizedQuery, testStationName)) {
                partialMatch = true;
            }
        }
        return new StationMatchResult(exactMatch, partialMatch);
    }

    private String normalize(String query) {
        // Dashes are the same as spaces
        query = query.replaceAll("([- ])+", " ");
        // Parentheses are removed
        query = query.replaceAll("\\([^)]*+\\)", "");
        query = query.replace(" am ", " ");
        query = query.replace("  ", " ");
        return query.trim();
    }

    /**
     * Gives an object for an id.
     *
     * @param id can be a URI, a HAFAS id (7-digit number) or an old-style iRail id (BE.NMBS.{hafasid})
     * @return a Station or null
     */
    public Station getStationFromId(String id) {
        try {
            return stationByIdCache.get(id, () -> {
                if (this.stationsById == null) {
                    initializeStations();
                }

                String irailId = uriOrIdToIrailId(id);

                // The keys in our map are 9-digit ids
                return Optional.ofNullable(stationsById.get(irailId));
            }).orElse(null);
        } catch (UncheckedExecutionException | ExecutionException e) {
            log.error("Failed to get station for id {}", id, e);
            throw new InternalProcessingException(e.getCause());
        }
    }

    private String uriOrIdToIrailId(String id) {
        if (id.startsWith("http")) {
            id = id.substring(30);
        }
        if (id.startsWith("BE.NMBS.")) {
            id = id.substring(8);
        }
        if (id.length() == 7) {
            id = "00" + id;
        }
        return id;
    }

    private String standardizeQuery(String query) {
        query = query.replaceAll("Brussel Nat.+", "Brussels Airport");
        query = query.replaceAll("Brussels Airport ?-? ?Z?a?v?e?n?t?e?m?", "Brussels Airport");
        query = query.replace("- ", "-");
        query = query.replace("l alleud", "l'alleud");
        query = query.replace("L Alleud", "l'alleud");
        query = query.replace(" Cdg ", " Charles de Gaulle ");
        query = query.replace(" am ", " ");
        query = query.replace("frankfurt fl", "frankfurt main fl");
        query = query.replace("Bru.", "Brussel");
        query = query.replace("Brux.", "Bruxelles");
        query = query.replace("Maastricht Randwijck", "Maastricht Randwyck");
        query = query.replaceAll("\\s?\\(.*?\\)", "");
        query = query.replace("st-", "st ");
        query = query.replace("st.-", "st ");
        query = query.replaceAll(" st(\\s|$|\\.)", "st ");

        String[] parts = query.split("/");
        return parts[0].trim();
    }

    private String normalizeAccents(String str) {
        Map<String, String> unwanted = new HashMap<>();
        unwanted.put("Š", "S");
        unwanted.put("š", "s");
        unwanted.put("Ž", "Z");
        unwanted.put("ž", "z");
        unwanted.put("À", "A");
        unwanted.put("Á", "A");
        unwanted.put("Â", "A");
        unwanted.put("Ã", "A");
        unwanted.put("Ä", "A");
        unwanted.put("Å", "A");
        unwanted.put("Æ", "A");
        unwanted.put("Ç", "C");
        unwanted.put("È", "E");
        unwanted.put("É", "E");
        unwanted.put("Ê", "E");
        unwanted.put("Ë", "E");
        unwanted.put("Ì", "I");
        unwanted.put("Í", "I");
        unwanted.put("Î", "I");
        unwanted.put("Ï", "I");
        unwanted.put("Ñ", "N");
        unwanted.put("Ò", "O");
        unwanted.put("Ó", "O");
        unwanted.put("Ô", "O");
        unwanted.put("Õ", "O");
        unwanted.put("Ö", "O");
        unwanted.put("Ø", "O");
        unwanted.put("Ù", "U");
        unwanted.put("Ú", "U");
        unwanted.put("Û", "U");
        unwanted.put("Ü", "U");
        unwanted.put("Ý", "Y");
        unwanted.put("Þ", "Th");
        unwanted.put("ß", "Ss");
        unwanted.put("à", "a");
        unwanted.put("á", "a");
        unwanted.put("â", "a");
        unwanted.put("ã", "a");
        unwanted.put("ä", "a");
        unwanted.put("å", "a");
        unwanted.put("æ", "a");
        unwanted.put("ç", "c");
        unwanted.put("è", "e");
        unwanted.put("é", "e");
        unwanted.put("ê", "e");
        unwanted.put("ë", "e");
        unwanted.put("ì", "i");
        unwanted.put("í", "i");
        unwanted.put("î", "i");
        unwanted.put("ï", "i");
        unwanted.put("ð", "o");
        unwanted.put("ñ", "n");
        unwanted.put("ò", "o");
        unwanted.put("ó", "o");
        unwanted.put("ô", "o");
        unwanted.put("õ", "o");
        unwanted.put("ö", "o");
        unwanted.put("ø", "o");
        unwanted.put("ù", "u");
        unwanted.put("ú", "u");
        unwanted.put("û", "u");
        unwanted.put("ý", "y");
        unwanted.put("þ", "th");
        unwanted.put("œ", "oe");
        unwanted.put("ÿ", "y");
        unwanted.put("ü", "u");
        unwanted.put("Đ", "Dj");
        unwanted.put("đ", "dj");
        unwanted.put("Č", "C");
        unwanted.put("č", "c");
        unwanted.put("Ć", "C");
        unwanted.put("ć", "c");
        unwanted.put("Ŕ", "R");
        unwanted.put("ŕ", "r");

        for (Map.Entry<String, String> entry : unwanted.entrySet()) {
            str = str.replace(entry.getKey(), entry.getValue());
        }
        return str;
    }

    private boolean isQueryPartOfName(String query, String testStationName) {
        Pattern pattern = Pattern.compile(query, Pattern.CASE_INSENSITIVE);
        return pattern.matcher(testStationName).find()
                || pattern.matcher(testStationName.replace("'", " ")).find();
    }

    private boolean isEqualCaseInsensitive(String query, String testStationName) {
        Pattern pattern = Pattern.compile("^" + query + "$", Pattern.CASE_INSENSITIVE);
        return pattern.matcher(testStationName).matches()
                || pattern.matcher(testStationName.replace("'", " ")).matches();
    }

    private void putInFirstPlace(List<Station> list, Station value) {
        list.remove(value);
        list.addFirst(value);
    }

    /**
     * Reads all stations from the stations table using HQL.
     *
     * @return a list of all stations
     */
    public List<Station> getAllStations() {
        initializeStations();
        return new ArrayList<>(stationsById.values());
    }

    /**
     * Reads all stations from the stations table using HQL and initializes the stations map.
     */
    public synchronized void initializeStations() {
        if (this.stationsById != null) {
            return;
        }
        updateStationsFromDatabase();
    }

    synchronized void updateStationsFromDatabase() {
        log.info("Loading stations from database");
        List<Station> allStations = entityManager.createQuery("SELECT s FROM Station s", Station.class).getResultList();
        stationsSortedBySize = allStations.stream()
                .sorted(Comparator.comparing(Station::getAvgStopTimes, Comparator.nullsLast(Comparator.reverseOrder())))
                .collect(Collectors.toList());

        stationsById = new HashMap<>();
        for (Station station : allStations) {
            stationsById.put(station.getIrailId(), station);
        }
        log.info("Loaded {} stations from database", allStations.size());
    }

    private record StationMatchResult(boolean exactMatch, boolean partialMatch) {
    }

}



