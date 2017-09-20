<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 *
 * This class handles the data transfer from occupancy score tables.
 *
 * @author Stan Callewaert
 */

use MongoDB\Collection;

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
     * @param $epochTimestamp int The timestamp when the request was received
     */
    public static function processFeedback($feedback, $epochTimestamp)
    {
        date_default_timezone_set('Europe/Brussels');
        $dateParameter = substr($feedback['date'], -2) . substr($feedback['date'], -4, 2) . substr($feedback['date'], -6, 2);

        // Get information on this vehicle from the API
        $stops = self::getVehicleStopsInfo("http://api.irail.be/vehicle/?id=BE.NMBS." . basename($feedback['vehicle']) . '&date=' . $dateParameter);

        // If a destination is set, update the load for all stations inbetween. If not, only update in the given station.
        if (isset($feedback['to'])) {

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
        } else {
            self::processFeedbackOneConnection($feedback);
        }
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

    private static function feedbackOneConnectionToOccupancyTable($feedback)
    {
        $dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new MongoDB\Collection($m, $mongodb_db, 'occupancy');

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

    private static function feedbackOneConnectionToFeedbackTable($feedback)
    {
        $dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');
        $mongodb_db = getenv('MONGODB_DB');
        $m = new MongoDB\Driver\Manager($mongodb_url);
        $feedbackTable = new MongoDB\Collection($m, $mongodb_db, 'feedback');

        $feedbackData = array(
            'connection' => $feedback['connection'],
            'vehicle' => $feedback['vehicle'],
            'from' => $feedback['from'],
            'date' => $feedback['date'],
            'occupancy' => OccupancyOperations::URIToNumber($feedback['occupancy'])
        );

        $feedbackTable->insertOne($feedbackData);
    }
}