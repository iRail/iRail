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
    const UNKNOWN = 'https://api.irail.be/terms/unknown';
    const LOW = 'https://api.irail.be/terms/low';
    const MEDIUM = 'https://api.irail.be/terms/medium';
    const HIGH = 'https://api.irail.be/terms/high';

    public static function getOccupancy($vehicle, $date)
    {
        $dotenv = new Dotenv(dirname(dirname(__DIR__)));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new MongoDB\Collection($m, $mongodb_db, 'occupancy');

        // If we ever start using a date as parameter the parameter should be put here as date
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

        return self::NumberToURI($maxOccupancy);
    }

    public static function isCorrectPostURI($URI)
    {
        return $URI == self::LOW || $URI == self::MEDIUM || $URI == self::HIGH;
    }

    public static function getUnknown()
    {
        return self::UNKNOWN;
    }
}
