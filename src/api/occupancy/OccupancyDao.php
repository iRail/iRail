<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 *
 * This class handles the data transfer from occupancy score tables.
 *
 * @author Stan Callewaert
 */

namespace Irail\api\occupancy;

use Dotenv\Dotenv;
use MongoDB\Collection;
use MongoDB\Driver\Manager;

/**
 * Class OccupancyDao This class handles interactions with the feedback and occupancy collections in MongoDB
 * The feedback collection contains every feedback entry ever reported.
 * The occupancy collection contains one entry for every vehicle/date combination for which data has been reported,
 * containing the average feedback.
 */
class OccupancyDao
{
    /**
     * @param $feedback       array An array containing all the feedback data
     */
    public static function processFeedback($feedback)
    {
        date_default_timezone_set('Europe/Brussels');

        /*
         // If a destination is set, update the load for all stations inbetween. If not, only update in the given station.
         if (isset($feedback['to'])) {
             // Get information on this vehicle from the API
             $stops = self::getVehicleStopsInfo("http://api.irail.be/vehicle/?id=BE.NMBS." . basename($feedback['vehicle']) . '&date=' . $dateParameter);

             // Whether or not the station is inbetween the from and to station(including from).
             $stationIsInbetween = false;

             // Loop through all stops
             foreach ($stops->stop as $stop) {
                 if ($stop->station['URI'] == $feedback['from']) {
                     $stationIsInbetween = true;
                 }
                 if ($stop->station['URI'] == $feedback['to']) {
                     $stationIsInbetween = false;
                 }

                 if ($stationIsInbetween) {
                     $feedbackInBetween = $feedback;
                     // Set the from station (the station for which we are reporting) to the current station inbetween
                     $feedbackInBetween['from'] = (string) $stop->station['URI'];
                     // We store this data by id, but an id also contains the station for which we are reporting. Replace to resolve this.
                     // WARNING: this will break when the id format changes!
                     $feedbackInBetween['connection'] = str_replace(substr(basename($feedback['from']),2), substr(basename($feedbackInBetween['from']),2),
                         $feedbackInBetween['connection']);
                     self::processFeedbackOneConnection($feedbackInBetween);
                 }
             }
         } else {*/
        self::processFeedbackOneConnection($feedback);
        /*}*/
    }

    private static function getVehicleStopsInfo($vehicleURL)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $vehicleURL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // When using self-signed certificates:
        // curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($result);

        return $xml->stops;
    }

    private static function processFeedbackOneConnection($feedback)
    {
        self::feedbackOneConnectionToOccupancyTable($feedback);
        self::feedbackOneConnectionToFeedbackTable($feedback);
    }

    /**
     * Update the occupancy table. The occupancy table keeps one entry per connection id for which feedback has been
     * posted. This entry contains the average vote.
     *
     * @param $feedback
     */
    private static function feedbackOneConnectionToOccupancyTable($feedback)
    {
        self::initDotEnv();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');

        $m = new Manager($mongodb_url);
        $occupancy = new Collection($m, $mongodb_db, 'occupancy');

        $occupancyExists = $occupancy->findOne(array('connection' => $feedback['connection']));

        $occupancyData = array(
            'connection' => $feedback['connection'],
            'vehicle' => $feedback['vehicle'],
            'from' => $feedback['from'],
            'date' => $feedback['date'],
            'feedback' => OccupancyOperations::URIToNumber($feedback['occupancy']),
            'feedbackAmount' => 1,
            'occupancy' => OccupancyOperations::URIToNumber($feedback['occupancy'])
        );

        if (is_null($occupancyExists)) {
            $occupancy->insertOne($occupancyData);
        } else {
            if (is_null($occupancyExists['feedback'])) {
                $occupancy->updateOne(
                    array('connection' => $feedback['connection']),
                    array('$set' => array('feedback' => $occupancyData['feedback'], 'occupancy' => $occupancyData['feedback']))
                );
            } else {
                $feedbackAmount = $occupancyExists->feedbackAmount + 1;
                $feedbackScore = ((double)$occupancyExists->feedback * (double)$occupancyExists->feedbackAmount + (double)$occupancyData['feedback']) / (double)$feedbackAmount;

                $occupancy->updateOne(
                    array('connection' => $feedback['connection']),
                    array('$set' => array('feedbackAmount' => $feedbackAmount, 'feedback' => $feedbackScore, 'occupancy' => $feedbackScore))
                );
            }
        }
    }

    /**
     * Add a feedback entry to the feedback collection. The feedback collection is used to keep track of all feedback
     * ever posted.
     * @param $feedback
     */
    private static function feedbackOneConnectionToFeedbackTable($feedback)
    {
        self::initDotEnv();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');
        $m = new Manager($mongodb_url);
        $feedbackTable = new Collection($m, $mongodb_db, 'feedback');

        $feedbackData = array(
            'connection' => $feedback['connection'],
            'vehicle' => $feedback['vehicle'],
            'from' => $feedback['from'],
            'date' => $feedback['date'],
            'occupancy' => OccupancyOperations::URIToNumber($feedback['occupancy'])
        );

        $feedbackTable->insertOne($feedbackData);
    }

    private static function initDotEnv(): void
    {
        $dotenv = new Dotenv(dirname(__DIR__, 3));
        $dotenv->load();
    }
}
