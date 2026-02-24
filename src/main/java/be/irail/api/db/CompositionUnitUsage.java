package be.irail.api.db;

import jakarta.persistence.*;
import java.io.Serializable;
import java.util.Objects;

@Entity
@Table(name = "composition_unit_usage", schema = "public")
public class CompositionUnitUsage {

    @EmbeddedId
    private CompositionUnitUsageId id;

    @Column(name = "position", nullable = false)
    private Integer position; // tinyint in DB

    public CompositionUnitUsage() {}

    public CompositionUnitUsage(Long id, long uicCode, int i) {
        this.id = new CompositionUnitUsageId(uicCode, id);
        this.position = i;
    }

    public CompositionUnitUsageId getId() { return id; }
    public void setId(CompositionUnitUsageId id) { this.id = id; }

    public Integer getPosition() { return position; }
    public void setPosition(Integer position) { this.position = position; }

    @Embeddable
    public static class CompositionUnitUsageId implements Serializable {
        @Column(name = "uic_code")
        private long uicCode;

        @Column(name = "historic_composition_id")
        private long historicCompositionId;

        public CompositionUnitUsageId() {}

        public CompositionUnitUsageId(long uicCode, long historicCompositionId) {
            this.uicCode = uicCode;
            this.historicCompositionId = historicCompositionId;
        }

        public long getUicCode() { return uicCode; }
        public void setUicCode(long uicCode) { this.uicCode = uicCode; }

        public long getHistoricCompositionId() { return historicCompositionId; }
        public void setHistoricCompositionId(long historicCompositionId) { this.historicCompositionId = historicCompositionId; }

        @Override
        public boolean equals(Object o) {
            if (this == o) return true;
            if (o == null || getClass() != o.getClass()) return false;
            CompositionUnitUsageId that = (CompositionUnitUsageId) o;
            return Objects.equals(uicCode, that.uicCode) && Objects.equals(historicCompositionId, that.historicCompositionId);
        }

        @Override
        public int hashCode() {
            return Objects.hash(uicCode, historicCompositionId);
        }
    }
}
