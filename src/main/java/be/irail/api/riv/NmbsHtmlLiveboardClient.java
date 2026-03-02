package be.irail.api.riv;

import be.irail.api.db.OccupancyDao;
import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.*;
import be.irail.api.dto.result.LiveboardSearchResult;
import be.irail.api.exception.request.RequestOutsideTimetableRangeException;
import be.irail.api.exception.upstream.UpstreamServerException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import be.irail.api.riv.requests.LiveboardRequest;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Client for fetching and parsing NMBS liveboard data from the HTML website.
 * This is a fallback/alternative to the RIV API-based liveboard client.
 */
@Service
public class NmbsHtmlLiveboardClient {

    private static final Logger log = LoggerFactory.getLogger(NmbsHtmlLiveboardClient.class);
    private static final String LIVEBOARD_URL = "http://www.belgianrail.be/jp/nmbs-realtime/stboard.exe/nn";
    private static final Pattern VEHICLE_PATTERN = Pattern.compile("^(\\w+?)\\s*(\\d+)$");
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("dd/MM/yyyy");
    private static final DateTimeFormatter TIME_FORMATTER = DateTimeFormatter.ofPattern("H:mm:ss");

    private final HttpClient httpClient;
    private final StationsDao stationsDao;
    private final OccupancyDao occupancyDao;

    private static final Cache<String, CachedData<String>> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(1, TimeUnit.MINUTES)
            .build();

    public NmbsHtmlLiveboardClient(StationsDao stationsDao, OccupancyDao occupancyDao) {
        this.stationsDao = stationsDao;
        this.occupancyDao = occupancyDao;
        this.httpClient = HttpClient.newBuilder()
                .followRedirects(HttpClient.Redirect.NORMAL)
                .build();
    }

    /**
     * Fetches and parses a liveboard for the given request.
     *
     * @param request the liveboard request
     * @return the liveboard search result
     */
    public LiveboardSearchResult getLiveboard(LiveboardRequest request) {
        Station dbStation = request.station();
        StationDto currentStation = convertToModelStation(dbStation, request.language());

        String html = getLiveboardHtml(request, dbStation);
        List<DepartureOrArrival> entries = parseNmbsData(request, currentStation, html);
        return new LiveboardSearchResult(currentStation, entries);
    }

    private String getLiveboardHtml(LiveboardRequest request, Station station) {
        String cacheId = request.getCacheId();
        CachedData<String> cached = cache.getIfPresent(cacheId);
        if (cached != null) {
            return cached.getValue();
        }
        String html = fetchLiveboardHtml(request, station);
        cache.put(cacheId, new CachedData<>(html, 60));
        return html;
    }

    private String fetchLiveboardHtml(LiveboardRequest request, Station station) {
        String stationName = station.getName();
        String stationId = station.getIrailId();
        String formattedDate = request.dateTime().format(DATE_FORMATTER);
        String formattedTime = request.dateTime().format(TIME_FORMATTER);

        Map<String, String> parameters = new LinkedHashMap<>();
        parameters.put("ld", "std");
        parameters.put("boardType", request.timeSelection() == TimeSelection.DEPARTURE ? "dep" : "arr");
        parameters.put("time", formattedTime);
        parameters.put("date", formattedDate);
        parameters.put("maxJourneys", "50");
        parameters.put("wDayExtsq", "Ma|Di|Wo|Do|Vr|Za|Zo");
        parameters.put("input", stationName);
        parameters.put("inputRef", stationName + "#" + stationId.substring(2));
        parameters.put("REQ0JourneyStopsinputID",
                "A=1@O=" + stationName +
                        "@X=4356802@Y=50845649@U=80@L=" + stationId +
                        "@B=1@p=1669420371@n=ac.1=FA@n=ac.2=LA@n=ac.3=FS@n=ac.4=LS@n=ac.5=GA@");
        parameters.put("REQProduct_list", "5:1111111000000000");
        parameters.put("realtimeMode", "show");
        parameters.put("start", "yes");

        StringBuilder urlBuilder = new StringBuilder(LIVEBOARD_URL).append("?");
        parameters.forEach((key, value) -> urlBuilder
                .append(URLEncoder.encode(key, StandardCharsets.UTF_8))
                .append("=")
                .append(URLEncoder.encode(value, StandardCharsets.UTF_8))
                .append("&"));
        urlBuilder.deleteCharAt(urlBuilder.length() - 1);

        try {
            HttpRequest httpRequest = HttpRequest.newBuilder()
                    .uri(URI.create(urlBuilder.toString()))
                    .build();
            HttpResponse<String> response = httpClient.send(httpRequest, HttpResponse.BodyHandlers.ofString());

            if (response.statusCode() != 200) {
                throw new UpstreamServerException(
                        "Failed to fetch data from the NMBS website: " + response.statusCode());
            }

            String body = response.body();
            if (body.contains("vallen niet binnen de dienstregelingsperiode")) {
                throw new RequestOutsideTimetableRangeException(request.dateTime());
            }

            return cleanResponse(body);
        } catch (IOException | InterruptedException e) {
            Thread.currentThread().interrupt();
            throw new UpstreamServerException("Failed to fetch data from the NMBS website", e);
        }
    }

    /**
     * Cleans the HTML response by extracting the result table and removing unnecessary elements.
     *
     * @param responseBody the raw HTML response body
     * @return the cleaned HTML fragment
     */
    String cleanResponse(String responseBody) {
        Matcher matcher = Pattern.compile(
                "<table class=\"resultTable\" cellspacing=\"0\">.*?</table>",
                Pattern.DOTALL
        ).matcher(responseBody);

        if (!matcher.find()) {
            throw new UpstreamServerException("Could not find result table in NMBS HTML response");
        }
        String tableHtml = matcher.group(0);

        // Remove sqLinkRow rows
        tableHtml = tableHtml.replaceAll("<tr class=\"sqLinkRow.*?</tr>", "");
        // Remove onclick attributes
        tableHtml = tableHtml.replaceAll("onclick=\"loadDetails\\(.*?\\)\"", "");

        return tableHtml;
    }

    List<DepartureOrArrival> parseNmbsData(
            LiveboardRequest request,
            StationDto currentStation,
            String html
    ) {
        Document doc = Jsoup.parse(html);
        Elements stBoardEntries = doc.select("tr.stboard");

        List<DepartureOrArrival> departureArrivals = new ArrayList<>();
        boolean previousTimeWasDualDigit = request.dateTime().getHour() > 9;
        int daysForward = 0;

        for (Element entry : stBoardEntries) {
            boolean canceled = false;

            // Parse delay
            int delayMinutes = 0;
            Elements delayElements = entry.select("td.time span.prognosis");
            if (!delayElements.isEmpty()) {
                String delayText = delayElements.first().ownText().trim();
                if (!delayText.isEmpty()) {
                    if ("Cancelled".equals(delayText)) {
                        canceled = true;
                    } else {
                        String[] delayParts = delayText.trim().split(" ");
                        try {
                            delayMinutes = Integer.parseInt(delayParts[0].replace("+", ""));
                        } catch (NumberFormatException e) {
                            delayMinutes = 0;
                        }
                    }
                }
            }

            // Parse platform
            Elements platformChangeElements = entry.select("td.platform div.relative span.prognosis");
            String platform;
            boolean platformNormal = true;

            if (!platformChangeElements.isEmpty()) {
                platformNormal = false;
                platform = trimValue(platformChangeElements.first().ownText());
            } else {
                Element platformTd = entry.select("td.platform").first();
                if (platformTd != null) {
                    platform = trimValue(platformTd.ownText());
                    // Remove non-breaking spaces
                    platform = platform.replace("\u00a0", "");
                    // Take first line only
                    String[] platformLines = platform.split("\n");
                    platform = trimValue(platformLines[0]);
                } else {
                    platform = "?";
                }
            }
            if (platform.isEmpty()) {
                platform = "?";
            }

            // Parse time
            Element timeTd = entry.select("td.time").first();
            String timeText = trimValue(timeTd.ownText());
            timeText = timeText.split(" ")[0];

            boolean thisTimeIsDualDigit = !timeText.startsWith("0");
            if (!thisTimeIsDualDigit && previousTimeWasDualDigit) {
                daysForward++;
            }
            previousTimeWasDualDigit = thisTimeIsDualDigit;

            LocalDateTime dateTime = LocalDateTime.parse(
                    request.dateTime().format(DateTimeFormatter.ofPattern("yyyyMMdd")) + " " + timeText,
                    DateTimeFormatter.ofPattern("yyyyMMdd H:mm")
            );
            dateTime = dateTime.plusDays(daysForward);

            VehicleDirection vehicleDirection = parseDirection(entry, request.language());
            Vehicle vehicle = parseVehicle(entry, dateTime);
            vehicle.setDirection(vehicleDirection);

            DepartureOrArrival stopAtStation = new DepartureOrArrival();
            stopAtStation.setDelay(delayMinutes);
            stopAtStation.setStation(currentStation);
            stopAtStation.setScheduledDateTime(dateTime);
            stopAtStation.setVehicle(vehicle);
            stopAtStation.setPlatform(new PlatformInfo(currentStation.getId(), platform, !platformNormal));
            stopAtStation.setIsCancelled(canceled);
            stopAtStation.setIsExtra(false);

            // Occupancy
            stopAtStation.setOccupancy(occupancyDao.getOccupancy(stopAtStation));

            departureArrivals.add(stopAtStation);
        }

        return departureArrivals;
    }

    /**
     * Parses the vehicle type and number from a stboard entry.
     *
     * @param stBoardEntry      the table row element
     * @param scheduledStopTime the scheduled stop time, used to determine the journey start date
     * @return the parsed Vehicle
     */
    Vehicle parseVehicle(Element stBoardEntry, LocalDateTime scheduledStopTime) {
        Element productLink = stBoardEntry.select("td.product a").first();
        String vehicleTypeAndNumber = trimValue(productLink.text());

        // Some trains are split by a newline, some by a space
        vehicleTypeAndNumber = vehicleTypeAndNumber.replace("\n", " ");
        // Busses are completely missing a space, TRN trains are missing this sometimes
        vehicleTypeAndNumber = vehicleTypeAndNumber.replace("BUS", "BUS ");
        vehicleTypeAndNumber = vehicleTypeAndNumber.replace("TRN", "TRN ");
        // Replace possible double spaces after the space-introducing fixes
        vehicleTypeAndNumber = vehicleTypeAndNumber.replace("  ", " ");

        Matcher matcher = VEHICLE_PATTERN.matcher(vehicleTypeAndNumber);
        if (!matcher.matches()) {
            throw new UpstreamServerException(
                    "Could not parse vehicle type and number from: " + vehicleTypeAndNumber);
        }

        int journeyNumber = Integer.parseInt(matcher.group(2));
        String journeyType = matcher.group(1);

        LocalDate journeyStartDate = GtfsInMemoryDao.getInstance()
                .getStartDate(journeyNumber, scheduledStopTime)
                .orElse(scheduledStopTime.toLocalDate());

        return Vehicle.fromTypeAndNumber(journeyType, journeyNumber, journeyStartDate);
    }

    /**
     * Parses the direction station from a stboard entry.
     *
     * @param stBoardEntry the table row element
     * @param language     the requested language
     * @return the parsed VehicleDirection
     */
    VehicleDirection parseDirection(Element stBoardEntry, Language language) {
        Elements tds = stBoardEntry.select("td");
        Element directionTd = tds.get(1);
        Element directionLink = directionTd.selectFirst("a");

        String directionName;
        Station directionStation;

        if (directionLink != null && directionLink.hasAttr("href")) {
            directionName = trimValue(directionLink.text());
            // http://www.belgianrail.be/Station.ashx?lang=en&stationId=8885001
            String href = directionLink.attr("href");
            String directionHafasId = "00" + href.substring(57);
            directionStation = stationsDao.getStationFromId(directionHafasId);
        } else {
            directionName = trimValue(directionTd.text());
            List<Station> matchingStations = stationsDao.getStations(directionName);
            directionStation = matchingStations.isEmpty() ? null : matchingStations.getFirst();
        }

        StationDto directionStationDto = directionStation != null
                ? convertToModelStation(directionStation, language)
                : null;
        return new VehicleDirection(directionName, directionStationDto);
    }


    private StationDto convertToModelStation(Station dbStation, Language language) {
        if (dbStation == null) {
            return null;
        }
        return new StationDto(
                dbStation.getIrailId(),
                dbStation.getUri(),
                dbStation.getName(),
                dbStation.getName(language),
                dbStation.getLongitude(),
                dbStation.getLatitude()
        );
    }

    private static String trimValue(String str) {
        if (str == null) {
            return "";
        }
        return str.strip();
    }
}
