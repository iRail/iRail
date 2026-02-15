package be.irail.api.dto.vehiclecomposition;

public class TrainCompositionUnit {
    private final RollingMaterialType materialType;
    private boolean hasToilet;
    private boolean hasPrmToilet;
    private boolean hasTables;
    private boolean hasFirstClassOutlets;
    private boolean hasSecondClassOutlets;
    private boolean hasHeating;
    private boolean hasAirco;
    private boolean hasBikeSection;
    private String tractionType;
    private boolean canPassToNextUnit;
    private int standingPlacesSecondClass;
    private int standingPlacesFirstClass;
    private int seatsCoupeSecondClass;
    private int seatsCoupeFirstClass;
    private int seatsSecondClass;
    private int seatsFirstClass;
    private int lengthInMeter;
    private boolean hasSemiAutomaticInteriorDoors;
    private boolean hasLuggageSection;
    private int tractionPosition;
    private boolean hasPrmSection;
    private boolean hasPriorityPlaces;

    public TrainCompositionUnit(RollingMaterialType materialType) {
        this.materialType = materialType;
    }

    public RollingMaterialType getMaterialType() {
        return materialType;
    }

    public boolean hasToilet() {
        return hasToilet;
    }

    public TrainCompositionUnit setHasToilet(boolean hasToilet) {
        this.hasToilet = hasToilet;
        return this;
    }

    public boolean hasPrmToilet() {
        return hasPrmToilet;
    }

    public TrainCompositionUnit setHasPrmToilet(boolean hasPrmToilet) {
        this.hasPrmToilet = hasPrmToilet;
        return this;
    }

    public boolean hasTables() {
        return hasTables;
    }

    public TrainCompositionUnit setHasTables(boolean hasTables) {
        this.hasTables = hasTables;
        return this;
    }

    public boolean hasFirstClassOutlets() {
        return hasFirstClassOutlets;
    }

    public TrainCompositionUnit setHasFirstClassOutlets(boolean hasFirstClassOutlets) {
        this.hasFirstClassOutlets = hasFirstClassOutlets;
        return this;
    }

    public boolean hasSecondClassOutlets() {
        return hasSecondClassOutlets;
    }

    public TrainCompositionUnit setHasSecondClassOutlets(boolean hasSecondClassOutlets) {
        this.hasSecondClassOutlets = hasSecondClassOutlets;
        return this;
    }

    public boolean hasHeating() {
        return hasHeating;
    }

    public TrainCompositionUnit setHasHeating(boolean hasHeating) {
        this.hasHeating = hasHeating;
        return this;
    }

    public boolean hasAirco() {
        return hasAirco;
    }

    public TrainCompositionUnit setHasAirco(boolean hasAirco) {
        this.hasAirco = hasAirco;
        return this;
    }

    public boolean hasBikeSection() {
        return hasBikeSection;
    }

    public TrainCompositionUnit setHasBikeSection(boolean hasBikeSection) {
        this.hasBikeSection = hasBikeSection;
        return this;
    }

    public String getTractionType() {
        return tractionType;
    }

    public TrainCompositionUnit setTractionType(String tractionType) {
        this.tractionType = tractionType;
        return this;
    }

    public boolean canPassToNextUnit() {
        return canPassToNextUnit;
    }

    public TrainCompositionUnit setCanPassToNextUnit(boolean canPassToNextUnit) {
        this.canPassToNextUnit = canPassToNextUnit;
        return this;
    }

    public int getStandingPlacesSecondClass() {
        return standingPlacesSecondClass;
    }

    public TrainCompositionUnit setStandingPlacesSecondClass(int standingPlacesSecondClass) {
        this.standingPlacesSecondClass = standingPlacesSecondClass;
        return this;
    }

    public int getStandingPlacesFirstClass() {
        return standingPlacesFirstClass;
    }

    public TrainCompositionUnit setStandingPlacesFirstClass(int standingPlacesFirstClass) {
        this.standingPlacesFirstClass = standingPlacesFirstClass;
        return this;
    }

    public int getSeatsCoupeSecondClass() {
        return seatsCoupeSecondClass;
    }

    public TrainCompositionUnit setSeatsCoupeSecondClass(int seatsCoupeSecondClass) {
        this.seatsCoupeSecondClass = seatsCoupeSecondClass;
        return this;
    }

    public int getSeatsCoupeFirstClass() {
        return seatsCoupeFirstClass;
    }

    public TrainCompositionUnit setSeatsCoupeFirstClass(int seatsCoupeFirstClass) {
        this.seatsCoupeFirstClass = seatsCoupeFirstClass;
        return this;
    }

    public int getSeatsSecondClass() {
        return seatsSecondClass;
    }

    public TrainCompositionUnit setSeatsSecondClass(int seatsSecondClass) {
        this.seatsSecondClass = seatsSecondClass;
        return this;
    }

    public int getSeatsFirstClass() {
        return seatsFirstClass;
    }

    public TrainCompositionUnit setSeatsFirstClass(int seatsFirstClass) {
        this.seatsFirstClass = seatsFirstClass;
        return this;
    }

    public int getLengthInMeter() {
        return lengthInMeter;
    }

    public TrainCompositionUnit setLengthInMeter(int lengthInMeter) {
        this.lengthInMeter = lengthInMeter;
        return this;
    }

    public boolean hasSemiAutomaticInteriorDoors() {
        return hasSemiAutomaticInteriorDoors;
    }

    public TrainCompositionUnit setHasSemiAutomaticInteriorDoors(boolean hasSemiAutomaticInteriorDoors) {
        this.hasSemiAutomaticInteriorDoors = hasSemiAutomaticInteriorDoors;
        return this;
    }

    public boolean hasLuggageSection() {
        return hasLuggageSection;
    }

    public TrainCompositionUnit setHasLuggageSection(boolean hasLuggageSection) {
        this.hasLuggageSection = hasLuggageSection;
        return this;
    }

    public int getTractionPosition() {
        return tractionPosition;
    }

    public TrainCompositionUnit setTractionPosition(int tractionPosition) {
        this.tractionPosition = tractionPosition;
        return this;
    }

    public boolean hasPrmSection() {
        return hasPrmSection;
    }

    public TrainCompositionUnit setHasPrmSection(boolean hasPrmSection) {
        this.hasPrmSection = hasPrmSection;
        return this;
    }

    public boolean hasPriorityPlaces() {
        return hasPriorityPlaces;
    }

    public TrainCompositionUnit setHasPriorityPlaces(boolean hasPriorityPlaces) {
        this.hasPriorityPlaces = hasPriorityPlaces;
        return this;
    }

    public boolean hasSteeringCabin() {
        return materialType.getSubType().contains("X") || tractionType.equals("HLE");
    }

    public boolean isMultipleUnitMotorCar() {
        return materialType.getParentType().startsWith("AM");
    }
}
