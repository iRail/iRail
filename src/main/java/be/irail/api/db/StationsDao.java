package be.irail.api.db;

import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Repository;

import java.util.*;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

/**
 * This Dao searches through the stations list (in memory, after reading from the database once) according to the
 * irail/stations php logic.
 */
@Repository
public class StationsDao {

    private static final Logger log = LoggerFactory.getLogger(StationsDao.class);

    @PersistenceContext
    private EntityManager entityManager;

    private Map<String, Station> stationsById;
    private List<Station> stationsSortedBySize;
    private static final int STATION_SEARCH_RESULT_COUNT = 5;

    /**
     * Gets you stations in a list ordered by relevance to the optional query.
     *
     * @param query   The name or part of a name to search for
     * @param country shortcode for a country (e.g., be, de, fr...)
     * @return a list of stations
     */
    public List<Station> getStations(String query, String country) {
        if (query == null || query.isEmpty()) {
            return new ArrayList<>(this.stationsSortedBySize);
        }

        // Standardization and normalization
        String normalizedQuery = standardizeQuery(query);
        normalizedQuery = normalizeAccents(normalizedQuery);
        // Dashes are the same as spaces
        normalizedQuery = normalizedQuery.replaceAll("([- ])+", " ");

        int count = 0;
        List<Station> resultStations = new ArrayList<>();

        for (Station station : this.stationsSortedBySize) {
            if (country != null && !country.isEmpty() && !country.equalsIgnoreCase(station.getCountryCode())) {
                continue;
            }

            boolean exactMatch = false;
            boolean partialMatch = false;

            List<String> allNames = new ArrayList<>();
            allNames.add(station.getName());
            allNames.addAll(station.getLocalizedNames());
            // unique names
            allNames = allNames.stream().distinct().collect(Collectors.toList());

            for (String name : allNames) {
                String testStationName = normalizeAccents(name).replace(" am ", " ");
                testStationName = testStationName.replaceAll("([- ])+", " ");

                if (isEqualCaseInsensitive(normalizedQuery, testStationName)) {
                    exactMatch = true;
                    break;
                }

                if (isQueryPartOfName(normalizedQuery, testStationName)) {
                    partialMatch = true;
                }
            }

            if (exactMatch) {
                putInFirstPlace(resultStations, station);
                count++;
            } else if (partialMatch) {
                resultStations.add(station);
                count++;
            }

            if (count > STATION_SEARCH_RESULT_COUNT) {
                return resultStations;
            }
        }

        return resultStations;
    }

    /**
     * Gives an object for an id.
     *
     * @param id can be a URI, a HAFAS id (7-digit number) or an old-style iRail id (BE.NMBS.{hafasid})
     * @return a Station or null
     */
    public Station getStationFromId(String id) {
        if (this.stationsById == null) {
            initializeStations();
        }

        String irailId = uriOrIdToIrailId(id);

        // The keys in our map are 9-digit ids
        return stationsById.get(irailId);
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
        query = query.replaceAll("(?i)st(\\s|$|\\.)", "(saint|st|sint) ");

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
        return Pattern.compile(query, Pattern.CASE_INSENSITIVE).matcher(testStationName).find()
                || Pattern.compile(query, Pattern.CASE_INSENSITIVE).matcher(testStationName.replace("'", " ")).find();
    }

    private boolean isEqualCaseInsensitive(String query, String testStationName) {
        return Pattern.compile("^" + query + "$", Pattern.CASE_INSENSITIVE).matcher(testStationName).matches()
                || Pattern.compile("^" + query + "$", Pattern.CASE_INSENSITIVE).matcher(testStationName.replace("'", " ")).matches();
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
    private void initializeStations() {
        if (this.stationsById != null) {
            return;
        }
        log.info("Loading stations from database");
        List<Station> allStations = entityManager.createQuery("SELECT s FROM Station s", Station.class).getResultList();
        stationsSortedBySize = allStations.stream()
                .sorted(Comparator.comparing(Station::getAvgStopTimes, Comparator.nullsLast(Comparator.naturalOrder())))
                .toList();

        this.stationsById = allStations.stream().collect(Collectors.toMap(Station::getIrailId, station -> station));
        log.info("Loaded {} stations from database", allStations.size());
    }
}
