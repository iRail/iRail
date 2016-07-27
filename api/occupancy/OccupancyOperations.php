<?php
/* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class converts occupancy scores and URIs
   *
   * @author Stan Callewaert
   */

use Dotenv\Dotenv;
use MongoDB\Collection;

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
            if(class_exists(self::MONGODBCLASS)) {
                $occupancyDeparture = self::getOccupancyTrip($vehicle, $from, $date);

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
        $dotenv = new Dotenv(dirname(dirname(__DIR__)));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new MongoDB\Collection($m, $mongodb_db, 'occupancy');

        $connection = self::buildConnectionURI($vehicle, $from, $date);
        return $occupancy->findOne(array('connection' => $connection));
    }

    public static function getOccupancy($vehicle, $date)
    {
        $dotenv = new Dotenv(dirname(dirname(__DIR__)));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new MongoDB\Collection($m, $mongodb_db, 'occupancy');

        return $occupancy->find(array('vehicle' => $vehicle, 'date' => $date));
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
     * @param $occupancy
     * @return occupancyURI
     */
    public static function NumberToURI($occupancy)
    {
        if ($occupancy < 2/3) {
            return self::LOW;
        } elseif ($occupancy <= 4/3) {
            return self::MEDIUM;
        } else {
            return self::HIGH;
        }
    }

    public static function numberToURIExact($occupancy)
    {
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

    public static function URIToNumber($occupancy)
    {
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
