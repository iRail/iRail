package be.irail.api.db;

import be.irail.api.util.VehicleIdTools;
import jakarta.persistence.*;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "composition_history", schema = "public")
public class CompositionHistoryEntry {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "journey_type", nullable = false, length = 16)
    private String journeyType;

    @Column(name = "journey_number")
    private Integer journeyNumber;

    @Column(name = "journey_start_date")
    private LocalDate journeyStartDate;

    @Column(name = "from_station_id", nullable = false, length = 9)
    private String fromStationId;

    @Column(name = "to_station_id", nullable = false, length = 9)
    private String toStationId;

    @Column(name = "primary_material_type", length = 16)
    private String primaryMaterialType;

    @Column(name = "passenger_unit_count")
    private Integer passengerUnitCount; // tinyint in DB, Integer in Java is safe

    @Column(name = "created_at", insertable = false, updatable = false)
    private LocalDateTime createdAt;

    public CompositionHistoryEntry() {}

    public CompositionHistoryEntry(String id, String originId, String destinationId, LocalDate startDate, String primaryMaterialType, Integer passengerUnitCount) {
        this.journeyType = VehicleIdTools.extractTrainType(id);
        this.journeyNumber = VehicleIdTools.extractTrainNumber(id);
        this.fromStationId = originId;
        this.toStationId = destinationId;
        this.journeyStartDate = startDate;
        this.primaryMaterialType = primaryMaterialType;
        this.passengerUnitCount = passengerUnitCount;
    }

    public Long getId() { return id; }
    public void setId(Long id) { this.id = id; }

    public String getJourneyType() { return journeyType; }
    public void setJourneyType(String journeyType) { this.journeyType = journeyType; }

    public Integer getJourneyNumber() { return journeyNumber; }
    public void setJourneyNumber(Integer journeyNumber) { this.journeyNumber = journeyNumber; }

    public LocalDate getJourneyStartDate() { return journeyStartDate; }
    public void setJourneyStartDate(LocalDate journeyStartDate) { this.journeyStartDate = journeyStartDate; }

    public String getFromStationId() { return fromStationId; }
    public void setFromStationId(String fromStationId) { this.fromStationId = fromStationId; }

    public String getToStationId() { return toStationId; }
    public void setToStationId(String toStationId) { this.toStationId = toStationId; }

    public String getPrimaryMaterialType() { return primaryMaterialType; }
    public void setPrimaryMaterialType(String primaryMaterialType) { this.primaryMaterialType = primaryMaterialType; }

    public Integer getPassengerUnitCount() { return passengerUnitCount; }
    public void setPassengerUnitCount(Integer passengerUnitCount) { this.passengerUnitCount = passengerUnitCount; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }
}
