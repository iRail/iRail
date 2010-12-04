<?php
/**
 * Description of VehicleOutput
 *
 * @author pieterc
 */
include_once("Output.php");
abstract class VehicleOutput implements Output {

    protected $lang;
    function __construct($lang) {
        $this->lang = $lang;
    }

    protected function buildXML($route) {
        $lang = $this->lang;
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("vehicleinformation");
        $rootNode ->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));
        $xml->appendChild($rootNode);
        $vehicle = $xml->createElement("vehicle", $route->getVehicle());
        $vehicle ->setAttribute("locationX", $route -> getLocationX());
        $vehicle ->setAttribute("locationY", $route -> getLocationY());
        $rootNode -> appendChild($vehicle);

        $stops = $xml -> createElement("stops");
        $stops ->setAttribute("number", sizeof($route->getStops()));
        $i = 0;
        foreach ($route->getStops() as $stop) {
            $stopxml = $xml -> createElement("stop");
            $stopxml -> setAttribute("delay", $stop -> getDelay());
            $stopxml -> setAttribute("id", $i);
            $station = $xml->createElement("station", $stop -> getStation()->getName($lang));
            $station->setAttribute("id", $stop->getStation()->getId());
            $station->setAttribute("locationY", $stop->getStation()->getY());
            $station->setAttribute("locationX", $stop->getStation()->getX());
            $stopxml->appendChild($station);
            $time = $xml->createElement("time", $stop -> getTime());
            $time->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $stop -> getTime()));
            $stopxml->appendChild($time);
            $stops -> appendChild($stopxml);
            $i++;
        }

        $rootNode->appendChild($stops);
        return $xml;
    }
}
?>
