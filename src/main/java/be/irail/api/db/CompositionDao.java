package be.irail.api.db;

import be.irail.api.config.Metrics;
import be.irail.api.dto.Vehicle;
import be.irail.api.dto.result.VehicleCompositionSearchResult;
import be.irail.api.dto.vehiclecomposition.*;
import be.irail.api.util.VehicleIdTools;
import com.codahale.metrics.Timer;
import com.google.common.collect.ArrayListMultimap;
import jakarta.annotation.PostConstruct;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Repository;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.*;
import java.util.stream.Collectors;

/**
 * DAO for accessing train composition data.
 * Reads all compositions and linked composition unit usages starting from yesterday,
 * caches them in memory for efficient access.
 */
@Repository
public class CompositionDao {

    private static final Logger log = LogManager.getLogger(CompositionDao.class);

    private final StationsDao stationsDao;

    @PersistenceContext
    private EntityManager entityManager;

    @Autowired
    public CompositionDao(StationsDao stationsDao) {
        this.stationsDao = stationsDao;
    }

    private record DatedVehicleJourneyKey(int journeyId, LocalDate journeyStartDate) {
    }

    /**
     * Map from "journeyNumber|journeyStartDate" to list of CompositionHistory entries.
     */
    private final ArrayListMultimap<DatedVehicleJourneyKey, CompositionHistoryEntry> compositionsByJourneyKey = ArrayListMultimap.create();

    /**
     * Map from compositionHistoryId to list of CompositionUnitUsage entries.
     */
    private final ArrayListMultimap<Long, StoredCompositionUnit> unitsByCompositionId = ArrayListMultimap.create();

    /**
     * Map from uic code to list of CompositionUnitUsage entries.
     */
    private final Map<Long, StoredCompositionUnit> unitsByUicId = new HashMap<>();

    private final Timer storeCompositionTimer = Metrics.getRegistry().timer("CompositionDao, store");

    /**
     * Initializes the in-memory cache by reading compositions from yesterday onwards.
     */
    @PostConstruct
    public void initialize() {
        LocalDate yesterday = LocalDate.now().minusDays(1);

        // Read all historicCompositions with journey_start_date >= yesterday
        List<CompositionHistoryEntry> historicCompositions = entityManager.createQuery(
                "SELECT c FROM CompositionHistoryEntry c WHERE c.journeyStartDate >= :startDate",
                CompositionHistoryEntry.class
        ).setParameter("startDate", yesterday).getResultList();

        historicCompositions.stream().forEach(c -> compositionsByJourneyKey.put(new DatedVehicleJourneyKey(c.getJourneyNumber(), c.getJourneyStartDate()), c));
        historicCompositions.stream().collect(Collectors.groupingBy(CompositionHistoryEntry::getId));

        List<StoredCompositionUnit> storedCompositionUnits = entityManager.createQuery("SELECT u FROM StoredCompositionUnit u", StoredCompositionUnit.class).getResultList();
        unitsByUicId.putAll(storedCompositionUnits.stream().collect(Collectors.toMap(StoredCompositionUnit::getUicCode, u -> u)));

        // Read all usages for the relevant composition IDs
        List<Long> compositionIds = historicCompositions.stream().map(CompositionHistoryEntry::getId).toList();
        if (!compositionIds.isEmpty()) {
            List<CompositionUnitUsage> usages = entityManager.createQuery(
                    "SELECT u FROM CompositionUnitUsage u WHERE u.id.historicCompositionId IN :ids",
                    CompositionUnitUsage.class
            ).setParameter("ids", compositionIds).getResultList();

            usages.stream().sorted(Comparator.comparingInt(CompositionUnitUsage::getPosition))
                    .forEach(usage -> {
                        Long compositionId = usage.getId().getHistoricCompositionId();
                        unitsByCompositionId.put(compositionId, unitsByUicId.get(usage.getId().getUicCode()));
                    });
        }
    }

    /**
     * Gets the composition for a specific journey.
     *
     * @param vehicle          the vehicle to search
     * @param journeyStartDate the start date of the journey
     * @return the composition result containing history and unit usages, or null if not found
     */
    public List<TrainComposition> getComposition(Vehicle vehicle, LocalDate journeyStartDate) {
        DatedVehicleJourneyKey key = new DatedVehicleJourneyKey(vehicle.getNumber(), journeyStartDate);
        List<CompositionHistoryEntry> compositions = compositionsByJourneyKey.get(key);

        List<TrainComposition> results = new ArrayList<>();
        for (CompositionHistoryEntry composition : compositions) {
            Station origin = stationsDao.getStationFromId(composition.getFromStationId());
            Station destination = stationsDao.getStationFromId(composition.getToStationId());
            List<StoredCompositionUnit> units = unitsByCompositionId.get(composition.getId());
            TrainComposition result = new TrainComposition(vehicle, origin, destination, "Cache",
                    units.stream().map(this::convertUnit).toList());
            for (int i = 1; i < result.getUnits().size(); i++) {
                TrainCompositionUnit unit = result.getUnits().get(i);
                TrainCompositionUnit nextUnit = i < result.getUnits().size() - 1 ? result.getUnits().get(i + 1) : null;
                if (nextUnit != null && nextUnit.getTractionPosition() > unit.getTractionPosition()) {
                    unit.getMaterialType().setOrientation(RollingMaterialOrientation.RIGHT);
                }
                if (nextUnit != null
                        && unit.getMaterialType().getParentType().startsWith("AM")
                        && nextUnit.getMaterialType().getSubType().equals("a")) {
                    // next unit is a "a" carriage, this unit is the end of an AM set
                    unit.getMaterialType().setOrientation(RollingMaterialOrientation.RIGHT);
                }
                if (i == result.getUnits().size() - 1 && unit.getMaterialType().getParentType().startsWith("AM")) {
                    // last unit of a multi-unit
                    unit.getMaterialType().setOrientation(RollingMaterialOrientation.RIGHT);
                }
            }
            results.add(result);
        }

        return results;
    }

    @Transactional
    public void storeComposition(Vehicle vehicle, VehicleCompositionSearchResult result) {
        try (var timer = storeCompositionTimer.time()) {
            log.debug("Storing composition for vehicle {} with {} segments", vehicle.getId(), result.getSegments().size());

            for (TrainComposition segment : result.getSegments()) {
                storeCompositionSegment(segment);
            }
        } catch (Exception e) {
            log.error("Failed to store composition for vehicle {}: {}", vehicle.getId(), e.getMessage(), e);
        }
    }

    private void storeCompositionSegment(TrainComposition segment) {
        boolean isAnyUnitMissingId = segment.getUnits().stream()
                .map(u -> u instanceof TrainCompositionUnitWithId)
                .anyMatch(hasId -> !hasId);
        if (isAnyUnitMissingId) {
            log.warn("Composition segment {} has usages with missing IDs, ignoring. Contained {} usages.",
                    segment.getCompositionSource() + " " + segment.getOrigin().getName() + "-" + segment.getDestination().getName(),
                    segment.getLength());
            return;
        }

        CompositionHistoryEntry compositionHistoryEntry = convertTrainComposition(segment);
        entityManager.persist(compositionHistoryEntry);

        List<CompositionUnitUsage> usages = new ArrayList<>();
        var segmentUnits = segment.getUnits();
        for (int i = 0; i < segmentUnits.size(); i++) {
            TrainCompositionUnit apiUnit = segmentUnits.get(i);
            long uicCode = ((TrainCompositionUnitWithId) apiUnit).getUicCode();
            if (!unitsByUicId.containsKey(uicCode)) {
                log.info("Unit {} not found in database, inserting...", uicCode);
                StoredCompositionUnit unit = convertUnit((TrainCompositionUnitWithId) apiUnit);
                entityManager.persist(unit);
                unitsByUicId.put(uicCode, unit);
            }

            CompositionUnitUsage compositionUnitUsage = new CompositionUnitUsage(compositionHistoryEntry.getId(), uicCode, i);
            usages.add(compositionUnitUsage);
        }
        usages.forEach(entityManager::persist);
    }

    private CompositionHistoryEntry convertTrainComposition(TrainComposition trainComposition) {
        CompositionHistoryEntry entry = new CompositionHistoryEntry();
        entry.setJourneyType(VehicleIdTools.extractTrainType(trainComposition.getVehicle().getId()));
        entry.setJourneyNumber(VehicleIdTools.extractTrainNumber(trainComposition.getVehicle().getId()));
        entry.setFromStationId(trainComposition.getOrigin().getIrailId());
        entry.setToStationId(trainComposition.getDestination().getIrailId());
        entry.setJourneyStartDate(trainComposition.getVehicle().getJourneyStartDate());
        entry.setPrimaryMaterialType("Train");
        entry.setPassengerUnitCount((int) trainComposition.getUnits().stream()
                .filter(unit -> unit.getSeatsSecondClass() + unit.getSeatsFirstClass() > 0)
                .count());
        entry.setPrimaryMaterialType(trainComposition.getPrimarySubType());
        return entry;
    }

    private StoredCompositionUnit convertUnit(TrainCompositionUnitWithId apiUnit) {
        StoredCompositionUnit storedUnit = new StoredCompositionUnit();
        storedUnit.setUicCode(apiUnit.getUicCode());
        storedUnit.setMaterialTypeName(apiUnit.getMaterialType().getParentType());
        storedUnit.setMaterialSubtypeName(apiUnit.getMaterialType().getSubType());
        storedUnit.setMaterialNumber(apiUnit.getMaterialNumber());
        storedUnit.setHasToilet(apiUnit.hasToilet());
        storedUnit.setHasAirco(apiUnit.hasAirco());
        storedUnit.setSeatsFirstClass((short) apiUnit.getSeatsFirstClass());
        storedUnit.setSeatsSecondClass((short) apiUnit.getSeatsSecondClass());
        storedUnit.setHasBikeSection(apiUnit.hasBikeSection());
        storedUnit.setHasPrmSection(apiUnit.hasPrmSection());
        storedUnit.setHasPrmToilet(apiUnit.hasPrmToilet());
        return storedUnit;
    }

    private TrainCompositionUnit convertUnit(StoredCompositionUnit dbUnit) {
        RollingMaterialType materialType = new RollingMaterialType(
                dbUnit.getMaterialTypeName(),
                dbUnit.getMaterialSubtypeName()
        );

        TrainCompositionUnitWithId unit = new TrainCompositionUnitWithId(materialType);
        unit.setUicCode(dbUnit.getUicCode());
        unit.setMaterialNumber(dbUnit.getMaterialNumber());
        unit.setHasToilet(dbUnit.isHasToilet())
                .setHasAirco(dbUnit.isHasAirco())
                .setSeatsFirstClass(dbUnit.getSeatsFirstClass() != null ? dbUnit.getSeatsFirstClass() : 0)
                .setSeatsSecondClass(dbUnit.getSeatsSecondClass() != null ? dbUnit.getSeatsSecondClass() : 0)
                .setHasPrmSection(dbUnit.isHasPrmSection())
                .setHasBikeSection(dbUnit.isHasBikeSection());

        return unit;
    }
}
