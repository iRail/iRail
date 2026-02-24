package be.irail.api.dto;

import be.irail.api.db.Station;

/**
 * Represents a public transport station.
 * This model contains the station's identity, location, and naming information.
 */
public class StationDto {
    private final String id;
    private final String uri;
    private final String stationName;
    private final String localizedStationName;
    private final Double latitude;
    private final Double longitude;

    /**
     * Constructs a new Station with all required information.
     *
     * @param id the unique identifier for the station
     * @param uri the URI representing the station resource
     * @param stationName the standard name of the station
     * @param localizedStationName the name of the station in the requested language
     * @param longitude the geographic longitude of the station
     * @param latitude the geographic latitude of the station
     */
    public StationDto(
        String id,
        String uri,
        String stationName,
        String localizedStationName,
        Double longitude,
        Double latitude
    ) {
        this.id = id;
        this.uri = uri;
        this.stationName = stationName;
        this.localizedStationName = localizedStationName;
        this.latitude = latitude;
        this.longitude = longitude;
    }

    public StationDto(Station station, Language language) {
        this(station.getIrailId(), station.getUri(), station.getName(), station.getName(language), station.getLongitude(), station.getLatitude());
    }

    /**
     * Gets the unique identifier for the station.
     * @return the station ID
     */
    public String getId() {
        return this.id;
    }

    /**
     * Gets the URI representing the station resource.
     * @return the station URI
     */
    public String getUri() {
        return this.uri;
    }

    /**
     * Gets the standard name of the station.
     * @return the station name
     */
    public String getStationName() {
        return this.stationName;
    }

    /**
     * Gets the name of the station in the requested language.
     * @return the localized station name
     */
    public String getLocalizedStationName() {
        return this.localizedStationName;
    }

    /**
     * Gets the geographic latitude of the station.
     * @return the latitude, or null if not available
     */
    public Double getLatitude() {
        return this.latitude;
    }

    /**
     * Gets the geographic longitude of the station.
     * @return the longitude, or null if not available
     */
    public Double getLongitude() {
        return this.longitude;
    }
}
