<?php

/**
 * This class takes care of the output for all stations available.
 *
 * @author pieterc
 */
include_once("Output.php");

abstract class StationsOutput implements Output {

    protected function buildXML($stationsarray) {
        $xml = new DOMDocument("1.0", "UTF-8");
        $xmlstylesheet = $xml ->createProcessingInstruction("xml-stylesheet", "type='text/xsl' href='xmlstylesheets/stations.xsl'");
        $rootNode = $xml->createElement("stations");
        $rootNode ->appendChild($xmlstylesheet);
        $rootNode ->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $rootNode ->setAttribute("xsi:noNamespaceSchemaLocation", "stations.xsd");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));

        $xml->appendChild($rootNode);
        foreach ($stationsarray as $stat) {
            $station = $xml->createElement("station", $stat->getName());
            $station->setAttribute("id", $stat->getId());
            //provide also this tag for old versions
            $station->setAttribute("location", $stat->getY() . " " . $stat->getX());
            //new version
            $station->setAttribute("locationY", $stat->getY());
            $station->setAttribute("locationX", $stat->getX());
            $rootNode->appendChild($station);
        }
        return $xml;
    }

}
?>
