<?php

namespace Irail\Repositories\Nmbs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\CompositionUnavailableException;
use Irail\Exceptions\Internal\InternalProcessingException;
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
use Throwable;

class NmbsRivCompositionRepository implements VehicleCompositionRepository
{
    use Cache;

    const int COMPOSITION_MAX_DAYS_IN_FUTURE = 2;
    private StationsRepository $stationsRepository;
    private NmbsRivRawDataRepository $rivRawDataRepository;
    private GtfsTripStartEndExtractor $gtfsTripStartEndExtractor;

    public function __construct(
        StationsRepository $stationsRepository,
        NmbsRivRawDataRepository $rivRawDataRepository,
        GtfsTripStartEndExtractor $gtfsTripStartEndExtractor
    ) {
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
    public function getComposition(Vehicle $journey): VehicleCompositionSearchResult
    {
        $cacheKey = self::getCacheKey($journey);
        $cachedResult = $this->getCacheOrUpdate($cacheKey, function () use ($journey) {
            $cachedData = $this->getCompositionData($journey);
            $compositionData = $cachedData->getValue();

            $compositionData = $this->getCompositionPlan($compositionData, $journey);

            $journey = $this->standardizeJourneyType($journey); //
            $exception = false; // Track and save the last exception during parsing
            // Build a result
            $segments = [];
            foreach ($compositionData as $compositionDataForSingleSegment) {
                // NMBS does not have single-carriage railbusses,
                // meaning these compositions are only a locomotive and should be filtered out
                if (count($compositionDataForSingleSegment['materialUnits']) > 1) {
                    try {
                        $segments[] = $this->parseOneSegmentWithCompositionData(
                            $journey,
                            $compositionDataForSingleSegment
                        );
                    } catch (Throwable $e) {
                        $exception = $e;
                        Log::warning('Exception occured while trying to parse composition: ' . $exception->getMessage());
                    }
                } else {
                    Log::info('Skipping composition with less than 2 carriages');
                }
            }
            if ($exception && empty($segments)) {
                throw new InternalProcessingException(500, 'Failed to parse vehicle composition: ' . $exception->getMessage(), $exception);
            }
            $result = new VehicleCompositionSearchResult($journey, $segments);
            $result->mergeCacheValidity($cachedData->getCreatedAt(), $cachedData->getExpiresAt());
            return $result;
        }, rand(300, 360)); // Only recalculate at most once every 5 minutes
        $result = $cachedResult->getValue();
        $result->mergeCacheValidity($cachedResult->getCreatedAt(), $cachedResult->getExpiresAt()); // Combine cache validities to ensure we don't serve an "expires" value which lies in the past.
        return $result;
    }

    /**
     * @param Vehicle $journey
     * @return CachedData
     */
    public function getCompositionData(Vehicle $journey): CachedData
    {
        $journeyDate = $journey->getJourneyStartDate();
        $journeyWithOriginAndDestination = $this->gtfsTripStartEndExtractor->getVehicleWithOriginAndDestination($journey->getId(), $journeyDate);
        if (!$journeyWithOriginAndDestination) {
            Log::debug("Not fetching composition for journey {$journey->getId()} at date $journeyDate which could not be found in the GTFS feed.");
            throw new CompositionUnavailableException(
                $journey->getId(),
                'Composition unavailable. The vehicle may not be active on the given date.'
            );
        }
        $startTime = $journeyWithOriginAndDestination->getOriginDepartureTime();
        $secondsUntilStart = $startTime - Carbon::now()->timestamp;
        if ($secondsUntilStart > 86400 * self::COMPOSITION_MAX_DAYS_IN_FUTURE) {
            Log::debug("Not fetching composition for journey {$journey->getId()} at date $journeyDate which departs more than "
                . self::COMPOSITION_MAX_DAYS_IN_FUTURE . ' days in the future according to GTFS. '
                . "Start time is $startTime.");
            throw new CompositionUnavailableException(
                $journey->getId(),
                'Composition is only available from vehicle start, Vehicle is not active yet at this time.'
            );
        }

        return $this->fetchCompositionData($journey, $journeyWithOriginAndDestination);
    }

    private function parseOneSegmentWithCompositionData(Vehicle $vehicle, $travelSegmentWithCompositionData): TrainComposition
    {
        // Use the UIC code. id contains the PTCAR id, which for belgian stations maps to the TAF/TAP code but doesn't match for foreign stations.
        $origin = $this->stationsRepository->getStationById('00' . $travelSegmentWithCompositionData['ptCarFrom']['uicCode']);
        $destination = $this->stationsRepository->getStationById('00' . $travelSegmentWithCompositionData['ptCarTo']['uicCode']);

        $source = $travelSegmentWithCompositionData['confirmedBy'];
        $units = self::parseCompositionData($travelSegmentWithCompositionData['materialUnits']);

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
     * @param array $rawCompositionUnit StdClass the raw composition unit data.
     * @param int   $position The index/position of this vehicle.
     * @return TrainCompositionUnit The parsed and cleaned TrainCompositionUnit.
     */
    private static function parseCompositionUnit(array $rawCompositionUnit, int $position): TrainCompositionUnit
    {
        $rollingMaterialType = self::getMaterialType($rawCompositionUnit, $position);
        $compositionUnit = self::readDetailsIntoUnit($rawCompositionUnit, $rollingMaterialType);
        return $compositionUnit;
    }

    public static function getMaterialType(array $rawCompositionUnit, int $position): RollingMaterialType
    {
        if (
            (array_key_exists('tractionType', $rawCompositionUnit) && $rawCompositionUnit['tractionType'] == 'AM/MR')
            || (array_key_exists('materialSubTypeName', $rawCompositionUnit) && str_starts_with($rawCompositionUnit['materialSubTypeName'], 'AM'))
        ) {
            return self::getAmMrMaterialType($rawCompositionUnit, $position);
        } elseif (array_key_exists('tractionType', $rawCompositionUnit) && $rawCompositionUnit['tractionType'] == 'HLE') {
            return self::getHleMaterialType($rawCompositionUnit);
        } elseif (array_key_exists('tractionType', $rawCompositionUnit) && $rawCompositionUnit['tractionType'] == 'HV') {
            return self::getHvMaterialType($rawCompositionUnit);
        } elseif (array_key_exists('materialSubTypeName', $rawCompositionUnit) && str_contains($rawCompositionUnit['materialSubTypeName'], '_')) {
            // Anything else, default fallback
            $parentType = explode('_', $rawCompositionUnit['materialSubTypeName'])[0];
            $subType = explode('_', $rawCompositionUnit['materialSubTypeName'])[1];
            return new RollingMaterialType($parentType, $subType);
        }

        return new RollingMaterialType('unknown', 'unknown');
    }

    private static function readDetailsIntoUnit(array $rawComposition, RollingMaterialType $rollingMaterialType): TrainCompositionUnit
    {
        if (array_key_exists('uicCode', $rawComposition)) {
            // containing a uic code & material number
            $trainCompositionUnit = new TrainCompositionUnitWithId($rollingMaterialType);
            $trainCompositionUnit->setUicCode($rawComposition['uicCode']);
            $trainCompositionUnit->setMaterialNumber(self::readAttribute($rawComposition, 'materialNumber', 0));
            $trainCompositionUnit->setMaterialSubTypeName(self::readAttribute($rawComposition, 'materialSubTypeName', 'unknown'));
        } else {
            $trainCompositionUnit = new TrainCompositionUnit($rollingMaterialType);
        }
        $trainCompositionUnit->setHasToilet(self::readAttribute($rawComposition, 'hasToilets', false));
        $trainCompositionUnit->setHasPrmToilet(self::readAttribute($rawComposition, 'hasPrmToilets', false));
        $trainCompositionUnit->setHasTables(self::readAttribute($rawComposition, 'hasTables', false));
        $trainCompositionUnit->setHasBikeSection(self::readAttribute($rawComposition, 'hasBikeSection', false));
        $trainCompositionUnit->setHasSecondClassOutlets(self::readAttribute($rawComposition, 'hasSecondClassOutlets', false));
        $trainCompositionUnit->setHasFirstClassOutlets(self::readAttribute($rawComposition, 'hasFirstClassOutlets', false));
        $trainCompositionUnit->setHasHeating(self::readAttribute($rawComposition, 'hasHeating', false));
        $trainCompositionUnit->setHasAirco(self::readAttribute($rawComposition, 'hasAirco', false));
        $trainCompositionUnit->setHasPrmSection(self::readAttribute($rawComposition, 'hasPrmSection', false)); // Persons with Reduced Mobility
        $trainCompositionUnit->setHasPriorityPlaces(self::readAttribute($rawComposition, 'hasPriorityPlaces', false));
        $trainCompositionUnit->setTractionType(self::readAttribute($rawComposition, 'tractionType', 'unknown'));
        $trainCompositionUnit->setCanPassToNextUnit(self::readAttribute($rawComposition, 'canPassToNextUnit', false));
        $trainCompositionUnit->setStandingPlacesSecondClass(self::readAttribute($rawComposition, 'standingPlacesSecondClass', 0));
        $trainCompositionUnit->setStandingPlacesFirstClass(self::readAttribute($rawComposition, 'standingPlacesFirstClass', 0));
        $trainCompositionUnit->setSeatsCoupeSecondClass(self::readAttribute($rawComposition, 'seatsCoupeSecondClass', 0));
        $trainCompositionUnit->setSeatsCoupeFirstClass(self::readAttribute($rawComposition, 'seatsCoupeFirstClass', 0));
        $trainCompositionUnit->setSeatsSecondClass(self::readAttribute($rawComposition, 'seatsSecondClass', 0));
        $trainCompositionUnit->setSeatsFirstClass(self::readAttribute($rawComposition, 'seatsFirstClass', 0));
        $trainCompositionUnit->setLengthInMeter(self::readAttribute($rawComposition, 'lengthInMeter', 0));
        $trainCompositionUnit->setTractionPosition(self::readAttribute($rawComposition, 'tractionPosition', 0));
        $trainCompositionUnit->setHasSemiAutomaticInteriorDoors(self::readAttribute($rawComposition, 'hasSemiAutomaticInteriorDoors', false));
        $trainCompositionUnit->setHasLuggageSection(self::readAttribute($rawComposition, 'hasLuggageSection', false));

        return $trainCompositionUnit;
    }

    private static function readAttribute(array $rawComposition, string $key, bool|int $default)
    {
        if (!key_exists($key, $rawComposition)) {
            return $default;
        }
        return $rawComposition[$key];
    }

    /**
     * Get a unique key to identify data in the in-memory cache which reduces the number of requests to the NMBS.
     * @param Vehicle $journey The journey
     * @return string The key for the cached data.
     */
    public static function getCacheKey(Vehicle $journey): string
    {
        return 'NMBSComposition|' . $journey->getNumber() . '|' . $journey->getJourneyStartDate()->format('Ymd');
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
        if (array_key_exists('parentMaterialTypeName', $rawCompositionUnit)
            || array_key_exists('parentMaterialSubTypeName', $rawCompositionUnit)) {
            $parentType = $rawCompositionUnit['parentMaterialSubTypeName'];
            if (str_contains($parentType, '-')) {
                $parentType = explode('-', $parentType)[0]; // AM62-66 should become AM62
            }

            if ($parentType == 'AMFLIRTMC'){
                $parentType = 'FLIRT';
            }

            if (array_key_exists('materialSubTypeName', $rawCompositionUnit)) {
                $subType = explode('_', $rawCompositionUnit['materialSubTypeName'])[1]; // C
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
        if (array_key_exists('materialSubTypeName', $rawCompositionUnit)
            && str_starts_with($rawCompositionUnit['materialSubTypeName'], 'HLE')) {
            $parentType = substr($rawCompositionUnit['materialSubTypeName'], 0, 5); //HLE27
            $subType = substr($rawCompositionUnit['materialSubTypeName'], 5);
        } elseif (array_key_exists('materialTypeName', $rawCompositionUnit) && $rawCompositionUnit['materialTypeName'] == 'M7BMX') {
            $parentType = 'M7';
            $subType = 'BMX';
        } elseif (array_key_exists('materialSubTypeName', $rawCompositionUnit) && str_starts_with(
                $rawCompositionUnit['materialSubTypeName'],
            'M7'
        )) { // HV mislabeled as HLE :(
            $parentType = 'M7';
            $subType = substr($rawCompositionUnit['materialSubTypeName'], 2);
        } else {
            $parentType = substr($rawCompositionUnit['materialTypeName'], 0, 5); //HLE18
            $subType = substr($rawCompositionUnit['materialTypeName'], 5);
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
        if (array_key_exists('materialSubTypeName', $rawCompositionUnit)) {
            if (preg_match('/NS(\w+)$/', $rawCompositionUnit['materialSubTypeName'], $matches) == 1) {
                // International NS train handling
                $parentType = 'NS';
                $subType = $matches[1];
            } else {
                preg_match('/([A-Z]+\d+)(\s|_)?(.*)$/', $rawCompositionUnit['materialSubTypeName'], $matches);
                $parentType = $matches[1]; // M6, I11
                $subType = $matches[3]; // A, B, BDX, BUH, ...
            }
        } else {
            // Some special cases, typically when data is missing
            if (array_key_exists('materialTypeName', $rawCompositionUnit)) {
                $parentType = $rawCompositionUnit['materialTypeName'];
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
        $cachedJsonData = $this->rivRawDataRepository->getVehicleCompositionData($vehicle, $startStop);

        if ($cachedJsonData->getAge() == 0) { // If this cache entry was created now, adjust the expiration date based on contents
            $json = $cachedJsonData->getValue();
            $compositionData = $this->getCompositionPlan($json, $vehicle);
            if ($compositionData[0]['confirmedBy'] == 'Planning' || count($compositionData[0]['materialUnits']) < 2) {
                // Planning data often lacks detail. Store it for 5 minutes
                $cachedJsonData->setTtl(300 + rand(0, 30));
            } else {
                // Confirmed data can still change, but less often
                $cachedJsonData->setTtl(3600 + rand(0, 600));
            }
            $this->update($cachedJsonData);
        }

        return $cachedJsonData;
    }

    /**
     * @param mixed   $json
     * @param Vehicle $vehicle
     * @return mixed
     */
    public function getCompositionPlan(array $json, Vehicle $vehicle): mixed
    {
        // lastPlanned is likely the latest update, commercialPlanned is likely the timetabled planning
        $hasLastPlannedData = key_exists('lastPlanned', $json);
        $hasCommercialPlannedData = key_exists('commercialPlanned', $json);
        if (!$hasLastPlannedData && !$hasCommercialPlannedData) {
            throw new CompositionUnavailableException($vehicle->getId());
        }
        return $hasLastPlannedData ? $json['lastPlanned'] : $json['commercialPlanned'];
    }

    /**
     * @param Vehicle $journey
     * @return Vehicle
     */
    function standardizeJourneyType(Vehicle $journey): Vehicle
    {
        $journeyWithOriginAndDestination = $this->gtfsTripStartEndExtractor->getVehicleWithOriginAndDestination(
            $journey->getId(),
            $journey->getJourneyStartDate()
        );
        $journey = Vehicle::fromTypeAndNumber(
            $journeyWithOriginAndDestination->getJourneyType(),
            $journeyWithOriginAndDestination->getJourneyNumber(),
            $journey->getJourneyStartDate()
        );
        return $journey;
    }
}
