<?php

/**
 * This class takes care of the output for all stations available.
 *
 * @author pieterc
 */
include_once("Output.php");

abstract class StationsOutput implements Output {

    protected $lang;

    function __construct($lang) {
        $this->lang = $lang;
    }

    protected function buildXML($stationsarray) {
        $lang = $this->lang;
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("stations");
        $rootNode->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $rootNode->setAttribute("xsi:noNamespaceSchemaLocation", "stations.xsd");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));

        $xml->appendChild($rootNode);
        foreach ($stationsarray as $stat) {
            if (!isset($_GET["lang"])) {
                $previousname = array();
                foreach ($stat->getNames() as $name) {
                    if (!in_array($name, $previousname)) {
                        $station = $xml->createElement("station", $name);
                        $station->setAttribute("id", $stat->getId());
                        //provide also this tag for old versions
                        $station->setAttribute("location", $stat->getY() . " " . $stat->getX());
                        //new version
                        $station->setAttribute("locationY", $stat->getY());
                        $station->setAttribute("locationX", $stat->getX());
                        $rootNode->appendChild($station);
                        $previousname[sizeof($previousname)] = $name;
                    }
                }
            } else {
                $station = $xml->createElement("station", $stat->getName($lang));
                $station->setAttribute("id", $stat->getId());
                //provide also this tag for old versions
                $station->setAttribute("location", $stat->getY() . " " . $stat->getX());
                //new version
                $station->setAttribute("locationY", $stat->getY());
                $station->setAttribute("locationX", $stat->getX());
                $rootNode->appendChild($station);
            }
        }
        return $xml;
    }

}
?>
