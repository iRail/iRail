<?php

/**
 * Description of ConnectionOutput
 *
 * @author pieterc
 */
include_once("Output.php");

abstract class ConnectionOutput implements Output {

    protected $lang;
    function __construct($lang) {
        $this->lang = $lang;
    }

    protected function buildXML($connectionsarray) {
        $lang= $this->lang;
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("connections");
        $rootNode ->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $rootNode ->setAttribute("xsi:noNamespaceSchemaLocation", "connections.xsd");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));

        $xml->appendChild($rootNode);
        $conId = 0;
        foreach ($connectionsarray as $c) {
            /* @var $c Connection */
            $connection = $xml->createElement("connection");
            $connection->setAttribute("id", $conId);
            //DEPART
            $departure = $xml->createElement("departure");
            $departure->setAttribute("delay", $c->getDepart()->getDelay());

            $station = $xml->createElement("station", $c->getDepart()->getStation()->getName($lang));
            $station->setAttribute("id", $c->getDepart()->getStation()->getId());
            $station->setAttribute("location", $c->getDepart()->getStation()->getY() . " " . $c->getDepart()->getStation()->getX());
            $station->setAttribute("locationX", $c->getDepart()->getStation()->getX());
            $station->setAttribute("locationY", $c->getDepart()->getStation()->getY());
            $time0 = $xml->createElement("time", $c->getDepart()->getTime());
            $time0->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $c->getDepart()->getTime()));

            $platform = $xml->createElement("platform", $c->getDepart()->getPlatform());
            $platform->setAttribute("normal", $c->getDepart()->normalPlatform());

            $vehicle = $xml->createElement("vehicle", $c->getDepart()->getVehicle()->getId());

            $departure->appendChild($vehicle);
            $departure->appendChild($platform);
            $departure->appendChild($time0);
            $departure->appendChild($station);
            $connection->appendChild($departure);

            //ARRIVAL
            $arrival = $xml->createElement("arrival");
            $arrival->setAttribute("delay", $c->getArrival()->getDelay());

            $station = $xml->createElement("station", $c->getArrival()->getStation()->getName($lang));
            $station->setAttribute("id", $c->getArrival()->getStation()->getId());
            $station->setAttribute("locationX", $c->getArrival()->getStation()->getX());
            $station->setAttribute("locationY", $c->getArrival()->getStation()->getY());
            $station->setAttribute("location", $c->getArrival()->getStation()->getY() . " " . $c->getArrival()->getStation()->getX());

            $time1 = $xml->createElement("time", $c->getArrival()->getTime());
            //iso8601 standard for time: 2010-09-17T09:12:00Z
            $time1->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $c->getArrival()->getTime()));

            $platform = $xml->createElement("platform", $c->getArrival()->getPlatform());
            $platform->setAttribute("normal", $c->getArrival()->normalPlatform());

            $vehicle = $xml->createElement("vehicle", $c->getArrival()->getVehicle()->getId());

            $arrival->appendChild($vehicle);
            $arrival->appendChild($platform);
            $arrival->appendChild($time1);
            $arrival->appendChild($station);
            $connection->appendChild($arrival);

            //VIAS
            if (sizeof($c->getVias()) > 0) {
                $vias = $xml->createElement("vias");
                $vias->setAttribute("number", sizeof($c->getVias()));
                $k = 0;
                foreach ($c->getVias() as $v) {
                    $via = $xml->createElement("via");
                    $via->setAttribute("id", $k);

                    $arrivalv = $xml->createElement("arrival");
                    $platformv = $xml->createElement("platform", $v->getArrivalPlatform());
                    $timev = $xml->createElement("time", date("U", $v->getArrivalTime()));
                    $timev->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $v->getArrivalTime()));
                    $arrivalv->appendChild($platformv);
                    $arrivalv->appendChild($timev);

                    $departv = $xml->createElement("departure");
                    $platformv = $xml->createElement("platform", $v->getDepartPlatform());
                    $timev = $xml->createElement("time", date("U", $v->getDepartTime()));
                    $timev->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $v->getDepartTime()));
                    $departv->appendChild($platformv);
                    $departv->appendChild($timev);

                    $timeBetween = $xml->createElement("timeBetween", $v->getTimeBetween());

                    $stationv = $xml->createElement("station", $v->getStation()->getName($lang));
                    $stationv->setAttribute("location", $v->getStation()->getY() . " " . $v->getStation()->getX());
                    $stationv->setAttribute("id", $v->getStation()->getId());
                    $stationv->setAttribute("locationX", $v->getStation()->getX());
                    $stationv->setAttribute("locationY", $v->getStation()->getY());

                    $vehiclev = $xml->createElement("vehicle", $v->getVehicle()->getId());

                    $via->appendChild($arrivalv);
                    $via->appendChild($departv);
                    $via->appendChild($timeBetween);
                    $via->appendChild($stationv);
                    $via->appendChild($vehiclev);

                    $vias->appendChild($via);
                    $k++;
                }
                $connection->appendChild($vias);
            }

            //OTHER
            $duration = $xml->createElement("duration", $c->getDuration());
            //$duration -> setAttribute("formatted", date("H:i:s", $c -> getDuration())); // date functions are crazy in PHP... Don't understand this one...

            $connection->appendChild($duration);

            $rootNode->appendChild($connection);
            $conId++;
        }
        return $xml;
    }

}
?>
