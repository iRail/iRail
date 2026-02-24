package be.irail.api.db;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "composition_unit", schema = "public")
public class StoredCompositionUnit {
    @Id
    @Column(name = "uic_code", nullable = false)
    private Long uicCode;

    @Column(name = "material_type_name", nullable = false, length = 16)
    private String materialTypeName;

    @Column(name = "material_subtype_name", nullable = false, length = 16)
    private String materialSubtypeName;

    @Column(name = "material_number", nullable = false)
    private Integer materialNumber;

    @Column(name = "has_toilet", nullable = false)
    private boolean hasToilet = false;

    @Column(name = "has_prm_toilet", nullable = false)
    private boolean hasPrmToilet = false;

    @Column(name = "has_airco", nullable = false)
    private boolean hasAirco = false;

    @Column(name = "has_bike_section", nullable = false)
    private boolean hasBikeSection = false;

    @Column(name = "has_prm_section", nullable = false)
    private boolean hasPrmSection = false;

    @Column(name = "seats_first_class", nullable = false)
    private Short seatsFirstClass;

    @Column(name = "seats_second_class", nullable = false)
    private Short seatsSecondClass;

    @Column(name = "created_at", nullable = false, insertable = false, updatable = false)
    private LocalDateTime createdAt;

    @Column(name = "updated_at", nullable = false, insertable = false, updatable = false)
    private LocalDateTime updatedAt;

    public StoredCompositionUnit() {}

    public Long getUicCode() { return uicCode; }
    public void setUicCode(Long uicCode) { this.uicCode = uicCode; }

    public String getMaterialTypeName() { return materialTypeName; }
    public void setMaterialTypeName(String materialTypeName) { this.materialTypeName = materialTypeName; }

    public String getMaterialSubtypeName() { return materialSubtypeName; }
    public void setMaterialSubtypeName(String materialSubtypeName) { this.materialSubtypeName = materialSubtypeName; }

    public Integer getMaterialNumber() { return materialNumber; }
    public void setMaterialNumber(Integer materialNumber) { this.materialNumber = materialNumber; }

    public boolean isHasToilet() { return hasToilet; }
    public void setHasToilet(boolean hasToilet) { this.hasToilet = hasToilet; }

    public boolean hasPrmToilet() { return hasPrmToilet; }
    public void setHasPrmToilet(boolean hasPrmToilet) { this.hasPrmToilet = hasPrmToilet; }

    public boolean isHasAirco() { return hasAirco; }
    public void setHasAirco(boolean hasAirco) { this.hasAirco = hasAirco; }

    public boolean isHasBikeSection() { return hasBikeSection; }
    public void setHasBikeSection(boolean hasBikeSection) { this.hasBikeSection = hasBikeSection; }

    public boolean isHasPrmSection() { return hasPrmSection; }
    public void setHasPrmSection(boolean hasPrmSection) { this.hasPrmSection = hasPrmSection; }

    public Short getSeatsFirstClass() { return seatsFirstClass; }
    public void setSeatsFirstClass(Short seatsFirstClass) { this.seatsFirstClass = seatsFirstClass; }

    public Short getSeatsSecondClass() { return seatsSecondClass; }
    public void setSeatsSecondClass(Short seatsSecondClass) { this.seatsSecondClass = seatsSecondClass; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }

    public LocalDateTime getUpdatedAt() { return updatedAt; }
    public void setUpdatedAt(LocalDateTime updatedAt) { this.updatedAt = updatedAt; }
}
