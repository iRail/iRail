package be.irail.api.dto.vehiclecomposition;

/**
 * A train composition unit, including a UIC code and some additional fields.
 */
public class TrainCompositionUnitWithId extends TrainCompositionUnit {
    /**
     * @var int The UIC code of this vehicle
     */
    private long uicCode;

    /**
     * @var int The number for this car or motor-unit, visible to the traveler.
     */
    private int materialNumber;

    /**
     * @var String The material subtype name, as specified by the railway company. Examples are AM80_c or M6BUH.
     */
    private String materialSubTypeName;

    public TrainCompositionUnitWithId(RollingMaterialType materialType) {
        super(materialType);
    }

    public long getUicCode() {
        return this.uicCode;
    }

    public TrainCompositionUnitWithId setUicCode(long uicCode) {
        this.uicCode = uicCode;
        return this;
    }

    public int getMaterialNumber() {
        return this.materialNumber;
    }

    public TrainCompositionUnitWithId setMaterialNumber(int materialNumber) {
        this.materialNumber = materialNumber;
        return this;
    }

    public String getMaterialSubTypeName() {
        return this.materialSubTypeName;
    }

    public TrainCompositionUnitWithId setMaterialSubTypeName(String materialSubTypeName) {
        this.materialSubTypeName = materialSubTypeName;
        return this;
    }
}
