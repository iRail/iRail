<?php
/* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class handles the data transfer from occupancy score tables.
   *
   * @author Stan Callewaert
   */

use MongoDB\Collection;

class OccupancyDao
{
    public static function processFeedback($feedback, $epochFeedback)
    {
        date_default_timezone_set('Europe/Brussels');

        $stops = self::getVehicleStopsInfo($feedback['vehicle']);
        $errorCheck = 0;
        $lastStation = '';

        if (!is_null($feedback['to'])) {
            foreach ($stops->stop as $stop) {
                if ($errorCheck > 0) {
                    if ($epochFeedback < intval($stop->time) + intval($stop['delay'])) {
                        $feedback['from'] = $lastStation;
                        self::processFeedbackOneConnection($feedback);
                        break;
                    } else {
                        $lastStation = $stop->station['URI'][0];
                    }
                }

                if ($stop->station['URI'] == $feedback['from'] || $stop->station['URI'] == $feedback['to']) {
                    if ($errorCheck == 0) {
                        $lastStation = $stop->station['URI'][0];
                    }

                    $errorCheck += 1;
                }

                if ($errorCheck == 2) {
                    self::processFeedbackOneConnection($feedback);
                    break;
                }
            }
        } else {
            self::processFeedbackOneConnection($feedback);
        }
    }

    private static function getVehicleStopsInfo($URI)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $URI);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        curl_close($curl);

        $xml = simplexml_load_string($result);
        return $xml->stops;
    }

    private static function processFeedbackOneConnection($feedback)
    {
        $fromArr = (Array)$feedback['from'];
        $feedback['from'] = $fromArr[0];

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
