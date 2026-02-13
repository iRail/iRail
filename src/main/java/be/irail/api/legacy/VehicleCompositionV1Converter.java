package be.irail.api.legacy;

import be.irail.api.dto.Language;
import be.irail.api.dto.vehiclecomposition.RollingMaterialType;
import be.irail.api.dto.vehiclecomposition.TrainComposition;
import be.irail.api.dto.vehiclecomposition.TrainCompositionUnit;
import be.irail.api.dto.vehiclecomposition.TrainCompositionUnitWithId;

import java.util.List;

/**
 * Converts VehicleCompositionSearchResult to V1 DataRoot format for legacy API compatibility.
 */
public class VehicleCompositionV1Converter extends V1Converter {

    private final Language language;

    public VehicleCompositionV1Converter(Language language) {
        this.language = language;
    }

    public DataRoot convert(List<TrainComposition> searchResult) {
        DataRoot result = new DataRoot("vehicleinformation");
        result.composition = new V1CompositionWrapper();
        ((V1CompositionWrapper) result.composition).segment = searchResult.stream()
                .map(this::convertSegment)
                .toArray(V1Segment[]::new);
        return result;
    }

    private V1Segment convertSegment(TrainComposition segment) {
        V1Segment result = new V1Segment();
        result.origin = convertStation(segment.getOrigin().toDto(language));
        result.destination = convertStation(segment.getDestination().toDto(language));
        result.composition = convertComposition(segment);
        return result;
    }

    private V1Composition convertComposition(TrainComposition composition) {
        V1Composition result = new V1Composition();
        result.source = composition.getCompositionSource();
        result.unit = composition.getUnits().stream()
                .map(this::convertUnit)
                .toArray(V1Unit[]::new);
        return result;
    }

    private V1Unit convertUnit(TrainCompositionUnit unit) {
        V1Unit result = new V1Unit();
        if (unit instanceof TrainCompositionUnitWithId identifiedUnit) {
            result.materialNumber = identifiedUnit.getMaterialNumber();
        } else {
            result.materialNumber = null;
        }
        result.materialType = convertMaterialType(unit.getMaterialType());
        result.hasToilets = unit.hasToilet();
        result.hasSecondClassOutlets = unit.hasSecondClassOutlets();
        result.hasFirstClassOutlets = unit.hasFirstClassOutlets();
        result.hasHeating = unit.hasHeating();
        result.hasAirco = unit.hasAirco();
        result.materialNumber = unit instanceof TrainCompositionUnitWithId ? ((TrainCompositionUnitWithId) unit).getMaterialNumber() : null;
        result.tractionType = unit.getTractionType();
        result.canPassToNextUnit = unit.canPassToNextUnit();
        result.seatsFirstClass = unit.getSeatsFirstClass();
        result.seatsCoupeFirstClass = unit.getSeatsCoupeFirstClass();
        result.standingPlacesFirstClass = unit.getStandingPlacesFirstClass();
        result.seatsSecondClass = unit.getSeatsSecondClass();
        result.seatsCoupeSecondClass = unit.getSeatsCoupeSecondClass();
        result.standingPlacesSecondClass = unit.getStandingPlacesSecondClass();
        result.lengthInMeter = unit.getLengthInMeter();
        result.hasSemiAutomaticInteriorDoors = unit.hasSemiAutomaticInteriorDoors();
        result.materialSubTypeName = unit instanceof TrainCompositionUnitWithId ? ((TrainCompositionUnitWithId) unit).getMaterialSubTypeName() : null;
        result.tractionPosition = unit.getTractionPosition();
        result.hasPrmSection = unit.hasPrmSection();
        result.hasPriorityPlaces = unit.hasPriorityPlaces();
        result.hasBikeSection = unit.hasBikeSection();
        return result;
    }

    private V1MaterialType convertMaterialType(RollingMaterialType materialType) {
        if (materialType == null) {
            return null;
        }
        V1MaterialType result = new V1MaterialType();
        result.parent_type = materialType.getParentType();
        result.sub_type = materialType.getSubType();
        result.orientation = materialType.getOrientation() != null ? materialType.getOrientation().name() : null;
        return result;
    }

    // Inner classes for V1 output structure

    public class V1CompositionWrapper {
        public V1Segment[] segment;
    }

    public class V1Segment {
        public V1Station origin;
        public V1Station destination;
        public V1Composition composition;
    }

    public class V1Composition {
        public String source;
        public V1Unit[] unit;
    }

    public class V1Unit {
        public V1MaterialType materialType;
        public boolean hasToilets;
        public boolean hasSecondClassOutlets;
        public boolean hasFirstClassOutlets;
        public boolean hasHeating;
        public boolean hasAirco;
        public Integer materialNumber;
        public String tractionType;
        public boolean canPassToNextUnit;
        public int seatsFirstClass;
        public int seatsCoupeFirstClass;
        public int standingPlacesFirstClass;
        public int seatsSecondClass;
        public int seatsCoupeSecondClass;
        public int standingPlacesSecondClass;
        public int lengthInMeter;
        public boolean hasSemiAutomaticInteriorDoors;
        public String materialSubTypeName;
        public int tractionPosition;
        public boolean hasPrmSection;
        public boolean hasPriorityPlaces;
        public boolean hasBikeSection;
    }

    public class V1MaterialType {
        public String parent_type;
        public String sub_type;
        public String orientation;
    }
}
