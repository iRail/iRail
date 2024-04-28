<?php

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\CompositionUnavailableException;
use Irail\Models\CachedData;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\Vehicle;
use Irail\Models\VehicleComposition\RollingMaterialOrientation;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use Irail\Models\VehicleComposition\TrainCompositionUnitWithId;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Repositories\Gtfs\Models\JourneyWithOriginAndDestination;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Traits\Cache;
use stdClass;

class NmbsRivCompositionRepository implements VehicleCompositionRepository
{
    use Cache;

    const string NMBS_COMPOSITION_AUTH_CACHE_KEY = 'NMBSCompositionAuth';
    const int AUTH_KEY_CACHE_TTL = 60 * 30;
    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivRawDataRepository;
    private GtfsTripStartEndExtractor $gtfsTripStartEndExtractor;

    public function __construct(
        StationsRepository $stationsRepository,
        NmbsRivRawDataRepository $rivRawDataRepository,
        GtfsTripStartEndExtractor $gtfsTripStartEndExtractor
    )
    {
        $this->stationsRepository = $stationsRepository;
        $this->rivRawDataRepository = $rivRawDataRepository;
        $this->gtfsTripStartEndExtractor = $gtfsTripStartEndExtractor;
        $this->setCachePrefix('NmbsRivCompositionRepository');
    }

    /**
     * Scrape the composition of a train from the NMBS trainmap web application.
     * @param Vehicle $journey
     * @return VehicleCompositionSearchResult The response data. Null if no composition is available.
     */
    function getComposition(Vehicle $journey): VehicleCompositionSearchResult
    {
        return $this->getCacheOrUpdate($journey->getNumber(), function () use ($journey) {
            $journeyDate = $journey->getJourneyStartDate();
            $journeyWithOriginAndDestination = $this->gtfsTripStartEndExtractor->getVehicleWithOriginAndDestination($journey->getId(), $journeyDate);
            if (!$journeyWithOriginAndDestination) {
                Log::debug("Not fetching composition for journey {$journey->getId()} at date $journeyDate which could not be found in the GTFS feed.");
                throw new CompositionUnavailableException($journey->getId(),
                    'Composition is only available from vehicle start. Vehicle is not active on the given date.');
            }
            $startTimeOffset = $journeyWithOriginAndDestination->getOriginDepartureTimeOffset();
            $secondsUntilStart = ($journeyDate->timestamp + $startTimeOffset) - Carbon::now()->timestamp;
            if ($secondsUntilStart > 0) {
                Log::debug("Not fetching composition for journey {$journey->getId()} at date $journeyDate which has not departed yet according to GTFS. "
                    . "Start time is $startTimeOffset.");
                throw new CompositionUnavailableException($journey->getId(),
                    'Composition is only available from vehicle start, Vehicle is not active yet at this time.');
            }

            $cachedData = $this->fetchCompositionData($journey, $journeyWithOriginAndDestination);
            $compositionData = $cachedData->getValue();
            $cacheAge = $cachedData->getAge();

            // Build a result
            $segments = [];
            foreach ($compositionData as $compositionDataForSingleSegment) {
                // NMBS does not have single-carriage railbusses,
                // meaning these compositions are only a locomotive and should be filtered out
                if (count($compositionDataForSingleSegment->materialUnits) > 1) {
                    $segments[] = $this->parseOneSegmentWithCompositionData(
                        $journey,
                        $compositionDataForSingleSegment
                    );
                } else {
                    Log::info('Skipping composition with less than 2 carriages');
                }
            }
            return new VehicleCompositionSearchResult($journey, $segments);
        }, 60)->getValue();
    }

    private function parseOneSegmentWithCompositionData(Vehicle $vehicle, $travelSegmentWithCompositionData): TrainComposition
    {
        // Use the UIC code. id contains the PTCAR id, which for belgian stations maps to the TAF/TAP code but doesn't match for foreign stations.
        $origin = $this->stationsRepository->getStationById('00' . $travelSegmentWithCompositionData->ptCarFrom->uicCode);
        $destination = $this->stationsRepository->getStationById('00' . $travelSegmentWithCompositionData->ptCarTo->uicCode);

        $source = $travelSegmentWithCompositionData->confirmedBy;
        $units = self::parseCompositionData($travelSegmentWithCompositionData->materialUnits);

        // Set the left/right orientation on carriages. This can only be done by evaluating all carriages at the same time
        $units = self::setCorrectDirectionForCarriages($units);

        return new TrainComposition($vehicle, $origin, $destination, $source, $units);
    }

    /**
     * @param $rawUnits
     * @return TrainCompositionUnit[]
     */
    private static function parseCompositionData($rawUnits): array
    {
        $units = [];
        foreach ($rawUnits as $i => $compositionUnit) {
            $units[] = self::parseCompositionUnit($compositionUnit, $i);
        }
        return $units;
    }

    /**
     * Parse a train composition unit, typically one carriage or locomotive.
     * @param      $rawCompositionUnit StdClass the raw composition unit data.
     * @param int  $position The index/position of this vehicle.
     * @return TrainCompositionUnit The parsed and cleaned TrainCompositionUnit.
     */
    private static function parseCompositionUnit($rawCompositionUnit, int $position): TrainCompositionUnit
    {
        $rollingMaterialType = self::getMaterialType($rawCompositionUnit, $position);
        $compositionUnit = self::readDetailsIntoUnit($rawCompositionUnit, $rollingMaterialType);
        return $compositionUnit;
    }

    public static function getMaterialType($rawCompositionUnit, $position): RollingMaterialType
    {
        if (
            (property_exists($rawCompositionUnit, 'tractionType') && $rawCompositionUnit->tractionType == 'AM/MR')
            || (property_exists($rawCompositionUnit, 'materialSubTypeName') && str_starts_with($rawCompositionUnit->materialSubTypeName, 'AM'))
        ) {
            return self::getAmMrMaterialType($rawCompositionUnit, $position);
        } elseif (property_exists($rawCompositionUnit, 'tractionType') && $rawCompositionUnit->tractionType == 'HLE') {
            return self::getHleMaterialType($rawCompositionUnit);
        } elseif (property_exists($rawCompositionUnit, 'tractionType') && $rawCompositionUnit->tractionType == 'HV') {
            return self::getHvMaterialType($rawCompositionUnit);
        } elseif (str_contains($rawCompositionUnit->materialSubTypeName, '_')) {
            // Anything else, default fallback
            $parentType = explode('_', $rawCompositionUnit->materialSubTypeName)[0];
            $subType = explode('_', $rawCompositionUnit->materialSubTypeName)[1];
            return new RollingMaterialType($parentType, $subType);
        }

        return new RollingMaterialType('unknown', 'unknown');
    }

    private static function readDetailsIntoUnit($object, RollingMaterialType $rollingMaterialType): TrainCompositionUnit
    {
        if (property_exists($object, 'uicCode')) {
            // containing a uic code & material number
            $trainCompositionUnit = new TrainCompositionUnitWithId($rollingMaterialType);
            $trainCompositionUnit->setUicCode($object->uicCode);
            $trainCompositionUnit->setMaterialNumber($object->materialNumber ?: 0);
            $trainCompositionUnit->setMaterialSubTypeName($object->materialSubTypeName ?: 'unknown');
        } else {
            $trainCompositionUnit = new TrainCompositionUnit($rollingMaterialType);
        }
        $trainCompositionUnit->setHasToilet($object->hasToilets ?: false);
        $trainCompositionUnit->setHasPrmToilet($object->hasPrmToilets ?: false);
        $trainCompositionUnit->setHasTables($object->hasTables ?: false);
        $trainCompositionUnit->setHasBikeSection(property_exists($object, 'hasBikeSection') && $object->hasBikeSection);
        $trainCompositionUnit->setHasSecondClassOutlets($object->hasSecondClassOutlets ?: false);
        $trainCompositionUnit->setHasFirstClassOutlets($object->hasFirstClassOutlets ?: false);
        $trainCompositionUnit->setHasHeating($object->hasHeating ?: false);
        $trainCompositionUnit->setHasAirco($object->hasAirco ?: false);
        $trainCompositionUnit->setHasPrmSection(property_exists($object, 'hasPrmSection') && $object->hasPrmSection); // Persons with Reduced Mobility
        $trainCompositionUnit->setHasPriorityPlaces($object->hasPriorityPlaces ?: false);
        $trainCompositionUnit->setTractionType($object->tractionType ?: 'unknown');
        $trainCompositionUnit->setCanPassToNextUnit($object->canPassToNextUnit ?: false);
        $trainCompositionUnit->setStandingPlacesSecondClass($object->standingPlacesSecondClass ?: 0);
        $trainCompositionUnit->setStandingPlacesFirstClass($object->standingPlacesFirstClass ?: 0);
        $trainCompositionUnit->setSeatsCoupeSecondClass($object->seatsCoupeSecondClass ?: 0);
        $trainCompositionUnit->setSeatsCoupeFirstClass($object->seatsCoupeFirstClass ?: 0);
        $trainCompositionUnit->setSeatsSecondClass($object->seatsSecondClass ?: 0);
        $trainCompositionUnit->setSeatsFirstClass($object->seatsFirstClass ?: 0);
        $trainCompositionUnit->setLengthInMeter($object->lengthInMeter ?: 0);
        $trainCompositionUnit->setTractionPosition($object->tractionPosition ?: 0);
        $trainCompositionUnit->setHasSemiAutomaticInteriorDoors($object->hasSemiAutomaticInteriorDoors ?: false);
        $trainCompositionUnit->setHasLuggageSection(property_exists($object, 'hasLuggageSection') && $object->hasLuggageSection);

        return $trainCompositionUnit;
    }

    /**
     * Get a unique key to identify data in the in-memory cache which reduces the number of requests to the NMBS.
     * @param string $id The train id.
     * @return string The key for the cached data.
     */
    public static function getCacheKey(string $id): string
    {
        return 'NMBSComposition|' . $id;
    }

    /**
     * Guess the correct orientation for rolling stock. This way we only need to code this once, instead of every user for themselves.
     * - The carriages are looped over from left to right. The train drives to the left, so the first vehicle is in front.
     * - The default orientation is LEFT. This means a potential drivers cab is to the LEFT.
     * - The last carriage in a traction group has a RIGHT orientation. This means a potential drivers cab is to the RIGHT.
     *
     * @param TrainCompositionUnit[] $units
     * @return TrainCompositionUnit[]
     */
    private static function setCorrectDirectionForCarriages(array $units): array
    {
        if (empty($units)) {
            return $units;
        }
        for ($i = 1; $i < count($units) - 1; $i++) {
            // When discovering a carriage in another traction position,
            if ($units[$i]->getTractionPosition() < $units[$i + 1]->getTractionPosition()
                && (
                    !str_starts_with($units[$i]->getMaterialType()->getSubType(), 'M7')
                    || (self::isM7SteeringCabin($units[$i]) && self::isM7SteeringCabin($units[$i + 1])) // Two steering cabins after each other means right >< left coupling
                )
            ) {
                $units[$i]->getMaterialType()->setOrientation(RollingMaterialOrientation::RIGHT); // Switch orientation on the last vehicle in each traction group
            }
        }
        last($units)->getMaterialType()->setOrientation(RollingMaterialOrientation::RIGHT); // Switch orientation on the last vehicle of the train
        return $units;
    }

    private static function isM7SteeringCabin(TrainCompositionUnit $unit): bool
    {
        return $unit->getMaterialType()->getParentType() == 'M7'
            && ($unit->getMaterialType()->getSubType() == 'BMX' || $unit->getMaterialType()->getSubType() == 'BDXH');
    }

    /**
     * Handle the material type for AM/MR vehicles ( trains consisting of a type-specific number of motorized carriages which are always together, opposed to having a locomotive and unmotorized carriages).
     * @param                     $rawCompositionUnit
     * @param int                 $position
     */
    private static function getAmMrMaterialType($rawCompositionUnit, int $position): RollingMaterialType
    {
        // "materialSubTypeName": "AM80_c",
        // "parentMaterialSubTypeName": "AM80",
        // parentMaterialTypeName seems to be only present in case the subtype is not known. Therefore, it's presence indicates the lack of detailed data.
        if (property_exists($rawCompositionUnit, 'parentMaterialTypeName')
            || property_exists($rawCompositionUnit, 'parentMaterialSubTypeName')) {
            $parentType = $rawCompositionUnit->parentMaterialSubTypeName;
            if (str_contains($parentType, '-')) {
                $parentType = explode('-', $parentType)[0]; // AM62-66 should become AM62
            }
            if (property_exists($rawCompositionUnit, 'materialSubTypeName')) {
                $subType = explode('_', $rawCompositionUnit->materialSubTypeName)[1]; // C
            } else {
                // This data isn't available in the planning stage
                $subType = self::calculateAmMrSubType($parentType, $position);
            }
        } else {
            $parentType = 'Unknown AM/MR';
            $subType = '';
        }

        return new RollingMaterialType($parentType, $subType);
    }

    /**
     * @param string $parentType The parent type (e.g. AM08)
     * @param int    $position The 0-based index defining in which position the given carriage is.
     * @return string The subtype, typically A,B,C or D based on the position in a multiple-train unit.
     *                If a multiple-unit train with 5 carriages would be used, the 5th carriage would get subtype E.
     */
    private static function calculateAmMrSubType(string $parentType, int $position): string
    {
        // The NMBS data contains A for the first, B for the second, C for the third, ... carriage in an AM/MR/AR train.
        // We can "fix" their planning data for these types by setting A, B, C, ourselves.
        // We still communicate that this is unconfirmed data, so there isn't a problem if one of the trains has a wrong orientation.
        // Trains with 2 carriages:
        if (in_array($parentType, ['AM62-66', 'AM62', 'AM66', 'AM86'])) {
            switch ($position % 2) {
                case 0:
                    return 'A';
                case 1:
                    return 'B';
            }
        }

        // Trains with 3 carriages:
        if (in_array($parentType, ['AM08', 'AM08M', 'AM08P', 'AM96', 'AM96M', 'AM96P', 'AM80', 'AM80M', 'AM80P'])) {
            switch ($position % 3) {
                case 0:
                    return 'A';
                case 1:
                    return 'B';
                case 2:
                    return 'C';
            }
        }

        // Trains with 4 carriages:
        if ($parentType == 'AM75') {
            switch ($position % 3) {
                case 0:
                    return 'A';
                case 1:
                    return 'B';
                case 2:
                    return 'C';
                case 3:
                    return 'D';
            }
        }
        return 'unknown';
    }

    /**
     * Handle Electric Locomotives (HLE XX).
     * @param                     $rawCompositionUnit
     * @return RollingMaterialType
     */
    private static function getHleMaterialType($rawCompositionUnit): RollingMaterialType
    {
        // Electric locomotives
        if (property_exists($rawCompositionUnit, 'materialSubTypeName')
            && str_starts_with($rawCompositionUnit->materialSubTypeName, 'HLE')) {
            $parentType = substr($rawCompositionUnit->materialSubTypeName, 0, 5); //HLE27
            $subType = substr($rawCompositionUnit->materialSubTypeName, 5);
        } elseif (property_exists($rawCompositionUnit, 'materialTypeName') && $rawCompositionUnit->materialTypeName == 'M7BMX') {
            $parentType = 'M7';
            $subType = 'BMX';
        } elseif (property_exists($rawCompositionUnit, 'materialSubTypeName') && str_starts_with($rawCompositionUnit->materialSubTypeName,
                'M7')) { // HV mislabeled as HLE :(
            $parentType = 'M7';
            $subType = substr($rawCompositionUnit->materialSubTypeName, 2);
        } else {
            $parentType = substr($rawCompositionUnit->materialTypeName, 0, 5); //HLE18
            $subType = substr($rawCompositionUnit->materialTypeName, 5);
        }
        return new RollingMaterialType($parentType, $subType);
    }

    /**
     * Handle HV rolling stock (carriages which can be linked together however you want).
     * @param                     $rawCompositionUnit
     * @return mixed
     */
    private static function getHvMaterialType($rawCompositionUnit): RollingMaterialType
    {
        // Separate carriages
        if (property_exists($rawCompositionUnit, 'materialSubTypeName')) {
            if (preg_match('/NS(\w+)$/', $rawCompositionUnit->materialSubTypeName, $matches) == 1) {
                // International NS train handling
                $parentType = 'NS';
                $subType = $matches[1];
            } else {
                preg_match('/([A-Z]+\d+)(\s|_)?(.*)$/', $rawCompositionUnit->materialSubTypeName, $matches);
                $parentType = $matches[1]; // M6, I11
                $subType = $matches[3]; // A, B, BDX, BUH, ...
            }
        } else {
            // Some special cases, typically when data is missing
            if (property_exists($rawCompositionUnit, 'materialTypeName')) {
                $parentType = $rawCompositionUnit->materialTypeName;
            } else {
                $parentType = 'unknown';
            }
            $subType = 'unknown';
        }
        return new RollingMaterialType($parentType, $subType);
    }

    /**
     * @param Vehicle $vehicle
     * @return CachedData
     * @throws CompositionUnavailableException
     */
    private function fetchCompositionData(Vehicle $vehicle, JourneyWithOriginAndDestination $startStop): CachedData
    {
        $cacheKey = self::getCacheKey($vehicle->getNumber());
        if (!$this->isCached($cacheKey)) {
            try {
                $cachedData = $this->rivRawDataRepository->getVehicleCompositionData($vehicle, $startStop);
                $json = json_decode($cachedData->getValue());
                $compositionData = $this->getCompositionPlan($json, $vehicle);

                if ($compositionData[0]->confirmedBy == 'Planning' || count($compositionData[0]->materialUnits) < 2) {
                    // Planning data often lacks detail. Store it for 5 minutes
                    $this->setCachedObject($cacheKey, $compositionData, 5 * 60);
                } else {
                    // Confirmed data doesn't change and contains all details. This data dispersal after the train ride,
                    // so cache it long enough so it doesn't disappear instantly after the ride.
                    // TODO: data should not be cached for too long into the next day, or a departure date should be added to the query
                    $this->setCachedObject($cacheKey, $compositionData, 60 * 60 * 6);
                }
            } catch (CompositionUnavailableException $e) {
                // Cache "data unavailable" for 5 minutes to limit outgoing requests. Only do this after a fresh attempts,
                // so we don't keep increasing the TTL on every cache hit which returns null
                $this->setCachedObject($cacheKey, null, 300);
                throw $e;
            }
        }
        $compositionData = $this->getCachedObject($cacheKey);

        if ($compositionData == null || $compositionData->getValue() == null) {
            throw new CompositionUnavailableException($vehicle->getId());
        }

        return $compositionData;
    }

    /**
     * @param mixed   $json
     * @param Vehicle $vehicle
     * @return mixed
     */
    public function getCompositionPlan(mixed $json, Vehicle $vehicle): mixed
    {
        // lastPlanned is likely the latest update, commercialPlanned is likely the timetabled planning
        $hasLastPlannedData = property_exists($json, 'lastPlanned');
        $hasCommercialPlannedData = property_exists($json, 'commercialPlanned');
        if (!$hasLastPlannedData && !$hasCommercialPlannedData) {
            throw new CompositionUnavailableException($vehicle->getId());
        }
        $compositionData = $hasLastPlannedData ? $json->lastPlanned : $json->commercialPlanned;
        return $compositionData;
    }
}
