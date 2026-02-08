package be.irail.api.db;
import java.util.List;

import be.irail.api.dto.Language;
import be.irail.api.dto.StationDto;
import jakarta.persistence.Column;
import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

/**
 * Model representing a station as stored in the database.
 */
@Entity
@Table(name = "stations", schema = "public")
public class Station {
    @Id
    @Column(name = "uri", length = 50)
    private String uri;

    @Column(name = "name", nullable = false, length = 100)
    private String name;

    @Column(name = "alternative_fr", length = 100)
    private String alternativeFr;

    @Column(name = "alternative_nl", length = 100)
    private String alternativeNl;

    @Column(name = "alternative_de", length = 100)
    private String alternativeDe;

    @Column(name = "alternative_en", length = 100)
    private String alternativeEn;

    @Column(name = "taf_tap_code", length = 8)
    private String tafTapCode;

    @Column(name = "telegraph_code", length = 8)
    private String telegraphCode;

    @Column(name = "country_code", length = 2)
    private String countryCode;

    @Column(name = "longitude", nullable = false)
    private double longitude;

    @Column(name = "latitude", nullable = false)
    private double latitude;

    @Column(name = "avg_stop_times")
    private Double avgStopTimes;

    @Column(name = "official_transfer_time")
    private Integer officialTransferTime;

    public Station() {
    }

    public Station(String uri, String name, double longitude, double latitude) {
        this.uri = uri;
        this.name = name;
        this.longitude = longitude;
        this.latitude = latitude;
    }

    public StationDto toDto(Language language) {
        return new StationDto(this, language);
    }

    public List<String> getLocalizedNames() {
        List<String> names = new java.util.ArrayList<>();
        if (alternativeFr != null) names.add(alternativeFr);
        if (alternativeNl != null) names.add(alternativeNl);
        if (alternativeDe != null) names.add(alternativeDe);
        if (alternativeEn != null) names.add(alternativeEn);
        return names;
    }

    public String getUri() {
        return uri;
    }

    public void setUri(String uri) {
        this.uri = uri;
    }

    public String getIrailId() {
        // Remove leading http://irail.be/stations/NMBS/
        return uri.substring(30);
    }

    public String getHafasId() {
        // Remove leading http://irail.be/stations/NMBS/
        return uri.substring(32);
    }

    public String getName() {
        return name;
    }

    public String getName(Language language) {
        return switch (language) {
            case NL -> getAlternativeNl() != null ? getAlternativeNl() : getName();
            case FR -> getAlternativeFr() != null ? getAlternativeFr() : getName();
            case DE -> getAlternativeDe() != null ? getAlternativeDe() : getName();
            case EN -> getAlternativeEn() != null ? getAlternativeEn() : getName();
        };
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getAlternativeFr() {
        return alternativeFr;
    }

    public void setAlternativeFr(String alternativeFr) {
        this.alternativeFr = alternativeFr;
    }

    public String getAlternativeNl() {
        return alternativeNl;
    }

    public void setAlternativeNl(String alternativeNl) {
        this.alternativeNl = alternativeNl;
    }

    public String getAlternativeDe() {
        return alternativeDe;
    }

    public void setAlternativeDe(String alternativeDe) {
        this.alternativeDe = alternativeDe;
    }

    public String getAlternativeEn() {
        return alternativeEn;
    }

    public void setAlternativeEn(String alternativeEn) {
        this.alternativeEn = alternativeEn;
    }

    public String getTafTapCode() {
        return tafTapCode;
    }

    public void setTafTapCode(String tafTapCode) {
        this.tafTapCode = tafTapCode;
    }

    public String getTelegraphCode() {
        return telegraphCode;
    }

    public void setTelegraphCode(String telegraphCode) {
        this.telegraphCode = telegraphCode;
    }

    public String getCountryCode() {
        return countryCode;
    }

    public void setCountryCode(String countryCode) {
        this.countryCode = countryCode;
    }

    public double getLongitude() {
        return longitude;
    }

    public void setLongitude(double longitude) {
        this.longitude = longitude;
    }

    public double getLatitude() {
        return latitude;
    }

    public void setLatitude(double latitude) {
        this.latitude = latitude;
    }

    public Double getAvgStopTimes() {
        return avgStopTimes;
    }

    public void setAvgStopTimes(Double avgStopTimes) {
        this.avgStopTimes = avgStopTimes;
    }

    public Integer getOfficialTransferTime() {
        return officialTransferTime;
    }

    public void setOfficialTransferTime(Integer officialTransferTime) {
        this.officialTransferTime = officialTransferTime;
    }
}
