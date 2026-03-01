package be.irail.api.riv;

import be.irail.api.db.Station;
import be.irail.api.db.StationsDao;
import be.irail.api.dto.CachedData;
import be.irail.api.dto.Vehicle;
import be.irail.api.dto.result.VehicleCompositionSearchResult;
import be.irail.api.dto.vehiclecomposition.*;
import be.irail.api.exception.IrailHttpException;
import be.irail.api.exception.notfound.CompositionNotFoundException;
import be.irail.api.exception.notfound.JourneyNotFoundException;
import be.irail.api.gtfs.dao.GtfsInMemoryDao;
import com.fasterxml.jackson.databind.JsonNode;
import com.google.common.cache.Cache;
import com.google.common.cache.CacheBuilder;
import com.google.common.util.concurrent.UncheckedExecutionException;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.stereotype.Service;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.Optional;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.TimeUnit;

/**
 * Client for fetching and parsing NMBS Vehicle Composition data.
 */
@Service
public class NmbsRivCompositionClient {
    private static final Logger log = LogManager.getLogger(NmbsRivCompositionClient.class);

    private final StationsDao stationsDao;
    private final NmbsRivRawDataRepository rivRawDataRepository;

    private final Cache<Vehicle, VehicleCompositionSearchResult> cache = CacheBuilder.newBuilder()
            .maximumSize(2000)
            .expireAfterWrite(30, TimeUnit.MINUTES)
            .build();

    public NmbsRivCompositionClient(
            StationsDao stationsDao,
            NmbsRivRawDataRepository rivRawDataRepository
    ) {
        this.stationsDao = stationsDao;
        this.rivRawDataRepository = rivRawDataRepository;
    }

    public VehicleCompositionSearchResult getComposition(Vehicle journey) throws ExecutionException {
        try {
            return cache.get(journey, () -> {
                log.info("Fetching composition for vehicle {} from NMBS", journey.getId());
                Optional<JourneyWithOriginAndDestination> journeyWithOriginAndDestination = GtfsInMemoryDao.getInstance().getVehicleWithOriginAndDestination(
                        journey.getNumber(),
                        journey.getJourneyStartDate().atStartOfDay()
                );

                if (journeyWithOriginAndDestination.isEmpty()) {
                    throw new JourneyNotFoundException(journey.getId(), journey.getJourneyStartDate(), "Composition is only available from vehicle start. Vehicle is not active on the given date.");
                }

                CachedData<JsonNode> cachedData = rivRawDataRepository.getVehicleCompositionData(
                        String.valueOf(journey.getNumber()),
                        journeyWithOriginAndDestination.get().getOriginStopId().substring(0, 7),
                        journeyWithOriginAndDestination.get().getDestinationStopId().substring(0, 7),
                        journey.getJourneyStartDate()
                );

                JsonNode json = cachedData.getValue();
                JsonNode compositionData = json.has("lastPlanned") ? json.get("lastPlanned") : json.get("commercialPlanned");

                if (compositionData == null || !compositionData.isArray()) {
                    throw new CompositionNotFoundException(journey.getName(), journey.getJourneyStartDate());
                }

                List<TrainComposition> segments = new ArrayList<>();
                for (JsonNode segmentNode : compositionData) {
                    JsonNode materialUnits = segmentNode.get("materialUnits");
                    if (materialUnits != null && materialUnits.isArray() && materialUnits.size() > 1) {
                        try {
                            segments.add(parseOneSegmentWithCompositionData(journey, segmentNode));
                        } catch (Exception e) {
                            log.warn("Failed to parse composition segment: {}", e.getMessage());
                        }
                    }
                }

                return new VehicleCompositionSearchResult(journey, segments);
            });
        } catch (ExecutionException | UncheckedExecutionException e) {
            if (e.getCause() instanceof IrailHttpException irailHttpException) {
                throw irailHttpException;
            } else {
                throw e;
            }
        }
    }

    private TrainComposition parseOneSegmentWithCompositionData(Vehicle vehicle, JsonNode segmentNode) {
        Station origin = stationsDao.getStationFromId("00" + segmentNode.get("ptCarFrom").get("uicCode").asText());
        Station destination = stationsDao.getStationFromId("00" + segmentNode.get("ptCarTo").get("uicCode").asText());

        String source = segmentNode.get("confirmedBy").asText();
        List<TrainCompositionUnit> units = parseCompositionData(segmentNode.get("materialUnits"));

        setCorrectDirectionForCarriages(units);

        return new TrainComposition(vehicle, origin, destination, source, units);
    }

    private List<TrainCompositionUnit> parseCompositionData(JsonNode rawUnits) {
        List<TrainCompositionUnit> units = new ArrayList<>();
        for (int i = 0; i < rawUnits.size(); i++) {
            units.add(parseCompositionUnit(rawUnits.get(i), i));
        }
        return units;
    }

    private TrainCompositionUnit parseCompositionUnit(JsonNode rawUnit, int position) {
        RollingMaterialType materialType = getMaterialType(rawUnit, position);
        return readDetailsIntoUnit(rawUnit, materialType);
    }

    private RollingMaterialType getMaterialType(JsonNode rawUnit, int position) {
        String tractionType = rawUnit.has("tractionType") ? rawUnit.get("tractionType").asText() : "";
        String materialSubTypeName = rawUnit.has("materialSubTypeName") ? rawUnit.get("materialSubTypeName").asText() : "";

        if ("AM/MR".equals(tractionType) || materialSubTypeName.startsWith("AM")) {
            return getAmMrMaterialType(rawUnit, position);
        } else if ("HLE".equals(tractionType)) {
            return getHleMaterialType(rawUnit);
        } else if ("HV".equals(tractionType)) {
            return getHvMaterialType(rawUnit);
        } else if (materialSubTypeName.contains("_")) {
            String[] parts = materialSubTypeName.split("_");
            return new RollingMaterialType(parts[0], parts[1]);
        }

        return new RollingMaterialType("unknown", "unknown");
    }

    private RollingMaterialType getAmMrMaterialType(JsonNode rawUnit, int position) {
        String parentType = "Unknown AM/MR";
        String subType = "";

        if (rawUnit.has("parentMaterialSubTypeName") || rawUnit.has("parentMaterialTypeName")) {
            parentType = rawUnit.has("parentMaterialSubTypeName") ? rawUnit.get("parentMaterialSubTypeName").asText() : rawUnit.get("parentMaterialTypeName").asText();
            if (parentType.contains("-")) {
                parentType = parentType.split("-")[0];
            }

            if (rawUnit.has("materialSubTypeName")) {
                String fullSubType = rawUnit.get("materialSubTypeName").asText();
                if (fullSubType.contains("_")) {
                    subType = fullSubType.split("_")[1];
                } else {
                    subType = calculateAmMrSubType(parentType, position);
                }
            } else {
                subType = calculateAmMrSubType(parentType, position);
            }
        }

        return new RollingMaterialType(parentType, subType);
    }

    private String calculateAmMrSubType(String parentType, int position) {
        List<String> twoCarriages = Arrays.asList("AM62-66", "AM62", "AM66", "AM86");
        if (twoCarriages.contains(parentType)) {
            return (position % 2 == 0) ? "A" : "B";
        }

        List<String> threeCarriages = Arrays.asList("AM08", "AM08M", "AM08P", "AM96", "AM96M", "AM96P", "AM80", "AM80M", "AM80P");
        if (threeCarriages.contains(parentType)) {
            return switch (position % 3) {
                case 0 -> "A";
                case 1 -> "B";
                case 2 -> "C";
                default -> "unknown";
            };
        }

        if ("AM75".equals(parentType)) {
            return switch (position % 4) {
                case 0 -> "A";
                case 1 -> "B";
                case 2 -> "C";
                case 3 -> "D";
                default -> "unknown";
            };
        }

        return "unknown";
    }

    private RollingMaterialType getHleMaterialType(JsonNode rawUnit) {
        String parentType;
        String subType;
        String materialSubTypeName = rawUnit.has("materialSubTypeName") ? rawUnit.get("materialSubTypeName").asText() : "";
        String materialTypeName = rawUnit.has("materialTypeName") ? rawUnit.get("materialTypeName").asText() : "";

        if (materialSubTypeName.startsWith("HLE")) {
            parentType = materialSubTypeName.substring(0, Math.min(materialSubTypeName.length(), 5));
            subType = materialSubTypeName.length() > 5 ? materialSubTypeName.substring(5) : "";
        } else if ("M7BMX".equals(materialTypeName)) {
            parentType = "M7";
            subType = "BMX";
        } else if (materialSubTypeName.startsWith("M7") || materialTypeName.startsWith("M6")) {
            parentType = "M7";
            subType = materialSubTypeName.substring(2);
        } else {
            parentType = materialTypeName.substring(0, Math.min(materialTypeName.length(), 5));
            subType = materialTypeName.length() > 5 ? materialTypeName.substring(5) : "";
        }
        return new RollingMaterialType(parentType, subType);
    }

    private RollingMaterialType getHvMaterialType(JsonNode rawUnit) {
        String parentType = "unknown";
        String subType = "unknown";

        if (rawUnit.has("materialSubTypeName")) {
            String subTypeName = rawUnit.get("materialSubTypeName").asText();
            if (subTypeName.endsWith("NS")) { // Simplified NS check
                parentType = "NS";
                subType = subTypeName.substring(0, subTypeName.length() - 2);
            } else {
                // Simplified regex-like logic: M6_A -> M6, A
                if (subTypeName.contains("_")) {
                    String[] parts = subTypeName.split("_");
                    parentType = parts[0];
                    subType = parts.length > 1 ? parts[1] : "unknown";
                } else if (subTypeName.startsWith("M6") || subTypeName.startsWith("M7")) {
                    parentType = subTypeName.substring(0, 2);
                    subType = subTypeName.substring(2);
                } else if (subTypeName.startsWith("I10")) {
                    parentType = subTypeName.substring(0, 3);
                    subType = subTypeName.substring(3);
                } else {
                    parentType = subTypeName;
                }
            }
        } else if (rawUnit.has("materialTypeName")) {
            parentType = rawUnit.get("materialTypeName").asText();
        }

        return new RollingMaterialType(parentType, subType);
    }

    private TrainCompositionUnit readDetailsIntoUnit(JsonNode object, RollingMaterialType materialType) {
        TrainCompositionUnit unit;
        if (object.has("uicCode")) {
            TrainCompositionUnitWithId unitWithId = new TrainCompositionUnitWithId(materialType);
            unitWithId.setUicCode(object.get("uicCode").asLong()); // Note: UIC code might be larger than int, but model says int
            unitWithId.setMaterialNumber(object.has("materialNumber") ? object.get("materialNumber").asInt() : 0);
            unitWithId.setMaterialSubTypeName(object.has("materialSubTypeName") ? object.get("materialSubTypeName").asText() : "unknown");
            unit = unitWithId;
        } else {
            unit = new TrainCompositionUnit(materialType);
        }

        unit.setHasToilet(object.has("hasToilets") && object.get("hasToilets").asBoolean());
        unit.setHasPrmToilet(object.has("hasPrmToilets") && object.get("hasPrmToilets").asBoolean());
        unit.setHasTables(object.has("hasTables") && object.get("hasTables").asBoolean());
        unit.setHasBikeSection(object.has("hasBikeSection") && object.get("hasBikeSection").asBoolean());
        unit.setHasSecondClassOutlets(object.has("hasSecondClassOutlets") && object.get("hasSecondClassOutlets").asBoolean());
        unit.setHasFirstClassOutlets(object.has("hasFirstClassOutlets") && object.get("hasFirstClassOutlets").asBoolean());
        unit.setHasHeating(object.has("hasHeating") && object.get("hasHeating").asBoolean());
        unit.setHasAirco(object.has("hasAirco") && object.get("hasAirco").asBoolean());
        unit.setHasPrmSection(object.has("hasPrmSection") && object.get("hasPrmSection").asBoolean());
        unit.setHasPriorityPlaces(object.has("hasPriorityPlaces") && object.get("hasPriorityPlaces").asBoolean());
        unit.setTractionType(object.has("tractionType") ? object.get("tractionType").asText() : "unknown");
        unit.setCanPassToNextUnit(object.has("canPassToNextUnit") && object.get("canPassToNextUnit").asBoolean());
        unit.setStandingPlacesSecondClass(object.has("standingPlacesSecondClass") ? object.get("standingPlacesSecondClass").asInt() : 0);
        unit.setStandingPlacesFirstClass(object.has("standingPlacesFirstClass") ? object.get("standingPlacesFirstClass").asInt() : 0);
        unit.setSeatsCoupeSecondClass(object.has("seatsCoupeSecondClass") ? object.get("seatsCoupeSecondClass").asInt() : 0);
        unit.setSeatsCoupeFirstClass(object.has("seatsCoupeFirstClass") ? object.get("seatsCoupeFirstClass").asInt() : 0);
        unit.setSeatsSecondClass(object.has("seatsSecondClass") ? object.get("seatsSecondClass").asInt() : 0);
        unit.setSeatsFirstClass(object.has("seatsFirstClass") ? object.get("seatsFirstClass").asInt() : 0);
        unit.setLengthInMeter(object.has("lengthInMeter") ? object.get("lengthInMeter").asInt() : 0);
        unit.setTractionPosition(object.has("tractionPosition") ? object.get("tractionPosition").asInt() : 0);
        unit.setHasSemiAutomaticInteriorDoors(object.has("hasSemiAutomaticInteriorDoors") && object.get("hasSemiAutomaticInteriorDoors").asBoolean());
        unit.setHasLuggageSection(object.has("hasLuggageSection") && object.get("hasLuggageSection").asBoolean());

        return unit;
    }

    private void setCorrectDirectionForCarriages(List<TrainCompositionUnit> units) {
        if (units.isEmpty()) {
            return;
        }

        for (int i = 1; i < units.size() - 1; i++) { // First position can be a steering cabin, which doesnt need turning
            TrainCompositionUnit current = units.get(i);
            TrainCompositionUnit next = units.get(i + 1);

            if (current.getTractionPosition() < next.getTractionPosition()) {
                if (!current.getMaterialType().getSubType().startsWith("M7") || (isM7SteeringCabin(current) && isM7SteeringCabin(next))) {
                    current.getMaterialType().setOrientation(RollingMaterialOrientation.RIGHT);
                }
            }
        }
        units.getLast().getMaterialType().setOrientation(RollingMaterialOrientation.RIGHT);
    }

    private boolean isM7SteeringCabin(TrainCompositionUnit unit) {
        String pt = unit.getMaterialType().getParentType();
        String st = unit.getMaterialType().getSubType();
        return "M7".equals(pt) && ("BMX".equals(st) || "BDXH".equals(st));
    }

}
