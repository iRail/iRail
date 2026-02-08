package be.irail.api.db;

import jakarta.persistence.*;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "occupancy_reports", schema = "public")
public class OccupancyReport {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "vehicle_id", nullable = false)
    private String vehicleId;

    @Column(name = "stop_id", nullable = false)
    private Integer stopId;

    @Column(name = "journey_start_date", nullable = false)
    private LocalDate journeyStartDate;

    @Column(name = "source", nullable = false)
    @Convert(converter = OccupancyReportSourceConverter.class)
    private OccupancyReportSource source;

    @Column(name = "occupancy", nullable = false)
    @Convert(converter = OccupancyLevelConverter.class)
    private OccupancyLevel occupancy;

    @Column(name = "created_at", insertable = false, updatable = false)
    private LocalDateTime createdAt;

    public OccupancyReport() {}

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }

    public String getVehicleId() { return vehicleId; }
    public void setVehicleId(String vehicleId) { this.vehicleId = vehicleId; }

    public Integer getStopId() { return stopId; }
    public void setStopId(Integer stopId) { this.stopId = stopId; }

    public LocalDate getJourneyStartDate() { return journeyStartDate; }
    public void setJourneyStartDate(LocalDate journeyStartDate) { this.journeyStartDate = journeyStartDate; }

    public OccupancyReportSource getSource() { return source; }
    public void setSource(OccupancyReportSource source) { this.source = source; }

    public OccupancyLevel getOccupancy() { return occupancy; }
    public void setOccupancy(OccupancyLevel occupancy) { this.occupancy = occupancy; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }
    
    public enum OccupancyReportSource {
        NMBS("NMBS"),
        SPITSGIDS("SG");

        private final String code;

        OccupancyReportSource(String code) {
            this.code = code;
        }

        public String getCode() {
            return code;
        }

        public static OccupancyReportSource fromCode(String code) {
            for (OccupancyReportSource source : OccupancyReportSource.values()) {
                if (source.code.equalsIgnoreCase(code)) {
                    return source;
                }
            }
            throw new IllegalArgumentException("Unknown OccupancyReportSource code: " + code);
        }
    }
    
    public enum OccupancyLevel {
        LOW(1),
        MEDIUM(2),
        HIGH(3);

        private final int value;

        OccupancyLevel(int value) {
            this.value = value;
        }

        public int getValue() {
            return value;
        }

        public static OccupancyLevel fromValue(Integer value) {
            if (value == null) return null;
            for (OccupancyLevel level : OccupancyLevel.values()) {
                if (level.value == value) {
                    return level;
                }
            }
            throw new IllegalArgumentException("Unknown OccupancyLevel value: " + value);
        }
    }

    @Converter(autoApply = true)
    public static class OccupancyReportSourceConverter implements AttributeConverter<OccupancyReportSource, String> {
        @Override
        public String convertToDatabaseColumn(OccupancyReportSource attribute) {
            return attribute != null ? attribute.getCode() : null;
        }

        @Override
        public OccupancyReportSource convertToEntityAttribute(String dbData) {
            return dbData != null ? OccupancyReportSource.fromCode(dbData) : null;
        }
    }

    @Converter(autoApply = true)
    public static class OccupancyLevelConverter implements AttributeConverter<OccupancyLevel, Integer> {
        @Override
        public Integer convertToDatabaseColumn(OccupancyLevel attribute) {
            return attribute != null ? attribute.getValue() : null;
        }

        @Override
        public OccupancyLevel convertToEntityAttribute(Integer dbData) {
            return dbData != null ? OccupancyLevel.fromValue(dbData) : null;
        }
    }
}
