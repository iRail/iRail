<?php
/* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class handles the data transfer from Spitsgids tables.
   *
   * @author Stan Callewaert
   */

use MongoDB\Collection;

class SpitsgidsController
{
    public static function processFeedback($feedback, $epoch)
    {
        date_default_timezone_set('Europe/Brussels');

        $stops = self::getVehicleStopsInfo($feedback["vehicle"]);
        $errorCheck = 0;
        $lastStation = "";

        foreach ($stops->stop as $stop) {
            if ($errorCheck > 0) {
                if ($epoch < intval($stop->time) + intval($stop["delay"])) {
                    $feedback["from"] = $lastStation;
                    self::processFeedbackOneConnection($feedback);
                    break;
                } else {
                    $lastStation = $stop->station["URI"];
                }
            }

            if ($stop->station["URI"] == $feedback["from"] || $stop->station["URI"] == $feedback["to"]) {
                if ($errorCheck == 0) {
                    $lastStation = $stop->station["URI"];
                }

                $errorCheck += 1;
            }

            if ($errorCheck == 2) {
                self::processFeedbackOneConnection($feedback);
                break;
            }
        }
    }

    private static function getVehicleStopsInfo($vehicle)
    {
        $url = "http://api.irail.be/vehicle/?id=BE.NMBS." . $vehicle;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

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

    private static function feedbackOneConnectionToOccupancyTable($feedback) {
        $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $occupancy = new MongoDB\Collection($m, 'spitsgids', 'occupancy');

        $id = $feedback["vehicle"] . "-" . $feedback["date"] . "-" . basename($feedback["from"]);
        $occupancyExists = $occupancy->findOne(array('id' => $id));

        $occupancyData = array(
            'id' => $id,
            'vehicle' => $feedback["vehicle"],
            'from' => $feedback["from"],
            'date' => $feedback["date"],
            'feedback' => OccupancyOperations::URIToNumber($feedback["occupancy"]),
            'feedbackAmount' => 1,
            'occupancy' => OccupancyOperations::URIToNumber($feedback["occupancy"])
        );

        if (is_null($occupancyExists)) {
            $occupancy->insertOne($occupancyData);
        } else {
            if (is_null($occupancyExists["feedback"])) {
                $occupancy->updateOne(
                    array('id' => $id),
                    array('$set' => array('feedback' => $occupancyData["feedback"], 'occupancy' => $occupancyData["feedback"]))
                );
            } else {
                $feedbackAmount = $occupancyExists->feedbackAmount + 1;
                $feedbackScore = ((double)$occupancyExists->feedback * (double)$occupancyExists->feedbackAmount + (double)$occupancyData["feedback"]) / (double)$feedbackAmount;

                $occupancy->updateOne(
                    array('id' => $id),
                    array('$set' => array('feedbackAmount' => $feedbackAmount, 'feedback' => $feedbackScore, 'occupancy' => $feedbackScore))
                );
            }
        }
    }

    private static function feedbackOneConnectionToFeedbackTable($feedback) {
        $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
        $dotenv->load();
        $mongodb_url = getenv('MONGODB_URL');

        $m = new MongoDB\Driver\Manager($mongodb_url);
        $feedbackTable = new MongoDB\Collection($m, 'spitsgids', 'feedback');

        $id = $feedback["vehicle"] . "-" . $feedback["date"] . "-" . basename($feedback["from"]);

        $feedbackData = array(
            'id' => $id,
            'vehicle' => $feedback["vehicle"],
            'from' => $feedback["from"],
            'date' => $feedback["date"],
            'occupancy' => OccupancyOperations::URIToNumber($feedback["occupancy"])
        );

        $feedbackTable->insertOne($feedbackData);
    }
}
}
