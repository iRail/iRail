<?php
/**
 * Description of VehicleOutput
 *
 * @author pieterc
 */
include_once("Output.php");
abstract class VehicleOutput implements Output {

    protected function buildXML($route) {
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("vehicleinformation");
        $rootNode ->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));
        $xml->appendChild($rootNode);

        $location = $xml->createElement("location");
        $location ->setAttribute("locationX", $route -> getLocationX());
        $location ->setAttribute("locationY", $route -> getLocationY());
        $rootNode -> appendChild($location);

        $stops = $xml -> createElement("stops");

        foreach ($route->getStops() as $stop) {
            $stopxml = $xml -> createElement("stop");
            $station = $xml->createElement("station", $stop -> getStation()->getName());
            $station->setAttribute("id", $stop->getStation()->getId());
            $station->setAttribute("locationY", $stop->getStation()->getY());
            $station->setAttribute("locationX", $stop->getStation()->getX());
            $stopxml->appendChild($station);
            $delay = $xml->createElement("delay", $stop -> getDelay());
            $stopxml->appendChild($delay);
            $time = $xml->createElement("time", $stop -> getTime());
            $time->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $stop -> getTime()));
            $stopxml->appendChild($time);
            $stops -> appendChild($stopxml);
        }

        $rootNode->appendChild($stops);
        return $xml;
    }
}
?>
