<?php
/* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class converts occupancy scores and URIs
   *
   * @author Stan Callewaert
   */

class OccupancyOperations {
    const UNKNOWN = 'https://api.irail.be/terms/unknown';
    const LOW = 'https://api.irail.be/terms/low';
    const MEDIUM = 'https://api.irail.be/terms/medium';
    const HIGH = 'https://api.irail.be/terms/high';

    public static function getOccupancyURI($vehicle, $from, $date) {
        $occupancyDeparture = self::getOccupancyTrip($vehicle, $from, $date);

        if(!is_null($occupancyDeparture)) {
            return self::NumberToURI($occupancyDeparture->occupancy);
        } else {
            return self::getUnknown();
        }
    }

    private static function getOccupancyTrip($vehicle, $from, $date) {
        $m = new MongoDB\Driver\Manager("mongodb://localhost:27017");
        $occupancy = new MongoDB\Collection($m, 'spitsgids', 'occupancy');

        return $occupancy->findOne(array('vehicle' => $vehicle, 'from' => $from, 'date' => $date));
    }

    public static function NumberToURI($occupancy) {
        if($occupancy < 2/3) {
            return self::LOW;
        } else if ($occupancy <= 4/3) {
            return self::MEDIUM;
        } else {
            return self::HIGH;
        }
    }

    private static function numberToURIExact($occupancy) {
        switch ($occupancy) {
            case 0:
                return self::LOW;
                break;
            case 1:
                return self::MEDIUM;
                break;
            case 2:
                return self::HIGH;
                break;
            default:
                return self::UNKNOWN;
                break;
        }
    }

    public static function URIToNumber($occupancy) {
        switch ($occupancy) {
            case self::LOW:
                return 0;
                break;
            case self::MEDIUM:
                return 1;
                break;
            case self::HIGH:
                return 2;
                break;
            default:
                return -1;
                break;
        }
    }

    public static function getMaxOccupancy($occupancyArr) {
        $maxOccupancy = -1;

        foreach ($occupancyArr as $occupancy) {
            $occupancyNumber = self::URIToNumber($occupancy);

            if($maxOccupancy < $occupancyNumber) {
                $maxOccupancy = $occupancyNumber;
            }
        }

        return self::numberToURIExact($maxOccupancy);
    }

    public static function getUnknown() {
        return self::UNKNOWN;
    }
}

?>