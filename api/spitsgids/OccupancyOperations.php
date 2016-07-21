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

    public static function NumberToURI($occupancy) {
        if($occupancy < 1/3) {
            return LOW;
        } else if ($occupancy <= 2/3) {
            return MEDIUM;
        } else {
            return HIGH;
        }
    }

    public static function URIToNumber($occupancy) {
        switch ($occupancy) {
            case LOW:
                return 0;
                break;
            case MEDIUM:
                return 1;
                break;
            case HIGH:
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

        return self::NumberToURI($maxOccupancy);
    }

    public static function isCorrectPostURI($URI) {
        return $URI == LOW || $URI == MEDIUM || $URI == HIGH;
    }

    public static function getUnknown() {
        return UNKNOWN;
    }
}

?>