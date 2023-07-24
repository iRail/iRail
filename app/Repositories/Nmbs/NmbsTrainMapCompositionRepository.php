<?php

namespace Irail\Repositories\Nmbs;

use Irail\Exceptions\CompositionUnavailableException;
use Irail\Http\Requests\VehicleCompositionRequest;
use Irail\Models\Result\VehicleCompositionSearchResult;
use Irail\Models\VehicleComposition\RollingMaterialOrientation;
use Irail\Models\VehicleComposition\RollingMaterialType;
use Irail\Models\VehicleComposition\TrainComposition;
use Irail\Models\VehicleComposition\TrainCompositionOnSegment;
use Irail\Models\VehicleComposition\TrainCompositionUnit;
use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\Nmbs\Tools\Tools;
use Irail\Repositories\VehicleCompositionRepository;
use Irail\Traits\Cache;
use stdClass;

class NmbsTrainMapCompositionRepository implements VehicleCompositionRepository
{

    use Cache;

    private StationsRepository $stationsRepository;

    public function __construct(StationsRepository $stationsRepository)
    {
        $this->stationsRepository = $stationsRepository;
    }

    /**
     * Scrape the composition of a train from the NMBS trainmap web application.
     * @param string $trainId
     * @return VehicleCompositionSearchResult The response data. Null if no composition is available.
     * @throws CompositionUnavailableException
     */

    function getComposition(VehicleCompositionRequest $request): VehicleCompositionSearchResult
    {
        $trainId = preg_replace('/[^0-9]/', '', $request->getVehicleId());
        try {
            $cacheKey = self::getCacheKey($trainId);
            $cacheAge = 0;
            if ($this->isCached($cacheKey)) {
                $compositionData = $this->getCachedObject($cacheKey);
                $cacheAge = $compositionData->getAge();
                $compositionData = $compositionData->getValue();
            } else {
                $compositionData = $this->getFreshCompositionData($trainId);
                if ($compositionData[0]->confirmedBy == 'Planning' || count($compositionData[0]->materialUnits) < 2) {
                    // Planning data often lacks detail. Store it for 5 minutes
                    $this->setCachedObject($cacheKey, $compositionData, 5 * 60);
                } else {
                    // Confirmed data doesn't change and contains all details. This data dispersal after the train ride,
                    // so cache it long enough so it doesn't disappear instantly after the ride.
                    // TODO: data should not be cached for too long into the next day, or a departure date should be added to the query
                    $this->setCachedObject($cacheKey, $compositionData, 60 * 60 * 6);
                }
            }
        } catch (CompositionUnavailableException $e) {
            // Cache "data unavailable" for 5 minutes to limit outgoing requests
            $this->setCachedObject($cacheKey, null, 300);
            throw $e;
        }

        // Build a result
        $segments = [];
        foreach ($compositionData as $compositionDataForSingleSegment) {
            $segments[] = $this->parseOneSegmentWithCompositionData(
                $compositionDataForSingleSegment
            );
        }

        return new VehicleCompositionSearchResult($segments);
    }

    private function parseOneSegmentWithCompositionData($travelSegmentWithCompositionData): TrainCompositionOnSegment
    {
        $origin = $this->stationsRepository->getStationByHafasId($travelSegmentWithCompositionData->ptCarFrom->uicCode);
        $destination = $this->stationsRepository->getStationByHafasId($travelSegmentWithCompositionData->ptCarTo->uicCode);
        $composition = self::parseCompositionData($travelSegmentWithCompositionData);

        // Set the left/right orientation on carriages. This can only be done by evaluating all carriages at the same time
        $composition = self::setCorrectDirectionForCarriages($composition);

        return new TrainCompositionOnSegment($origin, $destination, $composition);
    }

    private static function parseCompositionData($travelsegmentWithCompositionData): TrainComposition
    {
        $source = $travelsegmentWithCompositionData->confirmedBy;
        $units = [];
        foreach ($travelsegmentWithCompositionData->materialUnits as $i => $compositionUnit) {
            $units[] = self::parseCompositionUnit($compositionUnit, $i);
        }
        return new TrainComposition($source, $units);
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
        $unit = new TrainCompositionUnit($rollingMaterialType);
        $compositionUnit = self::readDetailsIntoUnit($rawCompositionUnit, $unit);
        return $compositionUnit;
    }

    public static function getMaterialType($rawCompositionUnit, $position): RollingMaterialType
    {
        if (
            (property_exists($rawCompositionUnit, "tractionType") && $rawCompositionUnit->tractionType == "AM/MR")
            || (property_exists($rawCompositionUnit, "materialSubTypeName") && str_starts_with($rawCompositionUnit->materialSubTypeName, "AM"))
        ) {
            return self::getAmMrMaterialType($rawCompositionUnit, $position);
        } else if (property_exists($rawCompositionUnit, "tractionType") && $rawCompositionUnit->tractionType == "HLE") {
            return self::getHleMaterialType($rawCompositionUnit);
        } else if (property_exists($rawCompositionUnit, "tractionType") && $rawCompositionUnit->tractionType == "HV") {
            return self::getHvMaterialType($rawCompositionUnit);
        } else if (str_contains($rawCompositionUnit->materialSubTypeName, '_')) {
            // Anything else, default fallback
            $parentType = explode('_', $rawCompositionUnit->materialSubTypeName)[0];
            $subType = explode('_', $rawCompositionUnit->materialSubTypeName)[1];
            return new RollingMaterialType($parentType, $subType);
        }

        return new RollingMaterialType('unknown', 'unknown');
    }

    private static function readDetailsIntoUnit($object, TrainCompositionUnit $trainCompositionUnit): TrainCompositionUnit
    {
        $trainCompositionUnit->setHasToilets($object->hasToilets ?: false);
        $trainCompositionUnit->setHasTables($object->hasTables ?: false);
        $trainCompositionUnit->setHasBikeSection(property_exists($object, 'hasBikeSection') && $object->hasBikeSection);
        $trainCompositionUnit->setHasSecondClassOutlets($object->hasSecondClassOutlets ?: false);
        $trainCompositionUnit->setHasFirstClassOutlets($object->hasFirstClassOutlets ?: false);
        $trainCompositionUnit->setHasHeating($object->hasHeating ?: false);
        $trainCompositionUnit->setHasAirco($object->hasAirco ?: false);
        $trainCompositionUnit->setHasPrmSection(property_exists($object, 'hasPrmSection') && $object->hasPrmSection); // Persons with Reduced Mobility
        $trainCompositionUnit->setHasPriorityPlaces($object->hasPriorityPlaces ?: false);
        $trainCompositionUnit->setMaterialNumber($object->materialNumber ?: 0);
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
        $trainCompositionUnit->setMaterialSubTypeName($object->materialSubTypeName ?: 'unknown');

        return $trainCompositionUnit;
    }

    /**
     * @param string $vehicleId The vehicle ID, numeric only. IC1234 should be passed as '1234'.
     * @return array The response data, or null when no data was found.
     */
    private function getFreshCompositionData(int $vehicleNumber): array
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => 30,
            'useragent' => Tools::getUserAgent()
        ];

        $ch = curl_init();
        $url = 'https://trainmapjs.azureedge.net/data/composition/' . $vehicleNumber;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);

        $authKey = $this->getAuthKey();
        $headers = [
            "auth-code: $authKey",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
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
     * @param TrainComposition $composition
     * @return TrainComposition
     */
    private static function setCorrectDirectionForCarriages(TrainComposition $composition): TrainComposition
    {
        $lastTractionGroup = $composition->getUnits()[0]->getTractionPosition();
        for ($i = 0; $i < count($composition->getUnits()); $i++) {
            if ($composition->getUnit($i)->getTractionPosition() > $lastTractionGroup) {
                $composition->getUnit($i - 1)->getMaterialType()->setOrientation(RollingMaterialOrientation::RIGHT); // Switch orientation on the last vehicle in each traction group
            }
            $lastTractionGroup = $composition->getUnits()[$i]->getTractionPosition();
        }
        $composition->getUnit($composition->getLength() - 1)->getMaterialType()->setOrientation(RollingMaterialOrientation::RIGHT); // Switch orientation on the last vehicle of the train
        return $composition;
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
        // parentMaterialTypeName seems to be only present in case the sub type is not known. Therefore, it's presence indicates the lack of detailed data.
        if (property_exists($rawCompositionUnit, 'parentMaterialTypeName')
            || property_exists($rawCompositionUnit, 'parentMaterialSubTypeName')) {
            $parentType = $rawCompositionUnit->parentMaterialSubTypeName;
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
            preg_match('/([A-Z]\d+)\s?(.*?)$/', $rawCompositionUnit->materialSubTypeName, $matches);
            $parentType = $matches[1]; // M6, I11
            $subType = $matches[2]; // A, B, BDX, BUH, ...
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
     * @param string $parentType
     * @param int    $position
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
                    return 'a';
                case 1:
                    return 'b';
            }
        }

        // Trains with 3 carriages:
        if (in_array($parentType, ['AM08', 'AM96', 'AM80', 'AM80m'])) {
            switch ($position % 3) {
                case 0:
                    return 'a';
                case 1:
                    return 'b';
                case 2:
                    return 'c';
            }
        }

        // Trains with 4 carriages:
        if (in_array($parentType, ['AM75'])) {
            switch ($position % 3) {
                case 0:
                    return 'a';
                case 1:
                    return 'b';
                case 2:
                    return 'c';
                case 3:
                    return 'd';
            }
        }
        return 'unknown';
    }

    /**
     * Get an authentication key for the composition API from cache if possible, or fresh if no key is cached.
     *
     * @return string|null the auth key, or null if it could not be obtained.
     */
    private function getAuthKey(): ?string
    {
        return $this->getCacheWithDefaultCacheUpdate(
            'NMBSCompositionAuth',
            fn() => self::getNewAuthKey(),
            60 * 30
        )->getValue();
    }

    /**
     * Get an authentication key for the composition API
     *
     * @return string|null the auth key, or null if it could not be obtained.
     */
    private static function getNewAuthKey(): ?string
    {
        $request_options = [
            'referer'   => 'http://api.irail.be/',
            'timeout'   => '30',
            'useragent' => Tools::getUserAgent(),
        ];

        $ch = curl_init();
        $url = "https://trainmap.belgiantrain.be/";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $html = curl_exec($ch);
        curl_close($ch);

        // Search for localStorage.setItem('tmAuthCode', "6c088db73a11de02eebfc0e5e4d38c75");
        preg_match("/localStorage\.setItem\(\"tmAuthCode\",\"(?<key>[A-Za-z0-9]+)\"\)/", $html, $matches);
        return $matches['key'];
    }
}
