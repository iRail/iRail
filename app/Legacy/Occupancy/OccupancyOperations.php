<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 *
 * This class converts occupancy scores and URIs
 *
 * @author Stan Callewaert
 */
namespace Irail\Legacy\Occupancy;

use Exception;

class OccupancyOperations
{
    const UNKNOWN = 'http://api.irail.be/terms/unknown';
    const LOW = 'http://api.irail.be/terms/low';
    const MEDIUM = 'http://api.irail.be/terms/medium';
    const HIGH = 'http://api.irail.be/terms/high';
    const CONNECTIONBASEURI = 'http://irail.be/connections/';
    const MONGODBCLASS = 'MongoDB\Driver\Manager';

    public static function getOccupancyURI($vehicle, $from, $date)
    {
        try {
            // Check if the MongoDB module is installed, if not just return null
            if (class_exists(self::MONGODBCLASS)) {
                $occupancyDeparture = self::getOccupancyTrip($vehicle, $from, $date);

                // If there is no occupancy for that connection, return unknown
                if (!is_null($occupancyDeparture)) {
                    return self::NumberToURI($occupancyDeparture->occupancy);
                } else {
                    return self::getUnknown();
                }
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw new Exception($e, 503);
        }
    }

    private static function getOccupancyTrip($vehicle, $from, $date)
    {
        $mongodb_url = $_ENV['MONGODB_URL'];
        $mongodb_db = $_ENV['MONGODB_DB'];

        $m = new \MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new \MongoDB\Collection($m, $mongodb_db, 'occupancy');

        $connection = self::buildConnectionURI($vehicle, $from, $date);
        return $occupancy->findOne(array('connection' => $connection));
    }

    public static function getOccupancy($vehicle, $date)
    {
        // Check if the MongoDB module is installed, if not just return null
        if (class_exists(self::MONGODBCLASS)) {
            $mongodb_url = getenv('MONGODB_URL');
            $mongodb_db = getenv('MONGODB_DB');

            $manager = new \MongoDB\Driver\Manager($mongodb_url);
            $occupancy = new \MongoDB\Collection($manager, $mongodb_db, 'occupancy');
            try {
                return $occupancy->find(array('vehicle' => $vehicle, 'date' => $date));
            } catch (Exception $e) {
                //Could not connect to db - give a response anyway
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * This function returns gets a number from 0 till 2.
     * It returns either the LOW, MEDIUM or HIGH URI.
     *
     * To do this one has to create borders.
     * Since there are 3 parts (LOW, MEDIUM and HIGH) 2 has to be diveded by 3 for each part.
     *
     * This implies that:
     * - LOW is from 0 till 2/3 (0,666...)
     * - MEDIUM is from 2/3 (0,666...) till 4/3 (1,333...)
     * - HIGH is from 4/3 (1,333...) till 2
     *
     * @param int $occupancy
     * @return string occupancyURI
     */
    public static function NumberToURI(int $occupancy) : string
    {
        if ($occupancy < 2/3) {
            return self::LOW;
        } elseif ($occupancy <= 4/3) {
            return self::MEDIUM;
        } else {
            return self::HIGH;
        }
    }

    public static function numberToURIExact(int $occupancy) : string
    {
        switch ($occupancy) {
            case 0:
                return self::LOW;
            case 1:
                return self::MEDIUM;
            case 2:
                return self::HIGH;
            default:
                return self::UNKNOWN;
        }
    }

    public static function URIToNumber(string $occupancy) : int
    {
        switch ($occupancy) {
            case self::LOW:
                return 0;
            case self::MEDIUM:
                return 1;
            case self::HIGH:
                return 2;
            default:
                return -1;
        }
    }

    public static function getMaxOccupancy($occupancyArr)
    {
        $maxOccupancy = -1;

        foreach ($occupancyArr as $occupancy) {
            $occupancyNumber = self::URIToNumber($occupancy);

            if ($maxOccupancy < $occupancyNumber) {
                $maxOccupancy = $occupancyNumber;
            }
        }

        return self::numberToURIExact($maxOccupancy);
    }

    public static function isCorrectPostURI($URI)
    {
        return $URI == self::LOW || $URI == self::MEDIUM || $URI == self::HIGH;
    }

    public static function getUnknown()
    {
        return self::UNKNOWN;
    }

    private static function buildConnectionURI($vehicle, $from, $date)
    {
        return self::CONNECTIONBASEURI . substr(basename($from), 2) . '/' . $date . '/' . basename($vehicle);
    }

}
