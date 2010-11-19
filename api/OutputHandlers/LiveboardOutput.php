<?php

/**
 * Description of LiveboardOutput
 *
 * @author pieterc
 */
include_once("Output.php");
include_once("DataStructs/Liveboard.php");

abstract class LiveboardOutput implements Output {
    protected function buildXML($liveboard) {
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("liveboard");
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));
        $xml->appendChild($rootNode);
        $liveboard->getStation();
        $station = $xml->createElement("station", $liveboard->getStation()->getName());
        $station->setAttribute("id", $liveboard->getStation()->getId());
        $station->setAttribute("locationY", $liveboard->getStation()->getY());
        $station->setAttribute("locationX", $liveboard->getStation()->getX());
        $rootNode->appendChild($station);
        foreach ($liveboard->getNodes() as $node) {
            $nodeEl = null;
            if ($liveboard->getDeparr() == "ARR") {
                $nodeEl = $xml->createElement("arrival");
            } else if ($liveboard->getDeparr() == "DEP") {
                $nodeEl = $xml->createElement("departure");
            }
            $nodeEl ->setAttribute("delay", $node -> getDelay() );
            $station = $xml->createElement("station", $node->getStation()->getName());
            $station->setAttribute("id", $node->getStation()->getId());
            $station->setAttribute("locationY", $node->getStation()->getY());
            $station->setAttribute("locationX", $node->getStation()->getX());
            $platform = $xml -> createElement("platform",$node -> getPlatform());

            $vehicle = $xml -> createElement("vehicle", $node -> getVehicle()->getId());
            $time = $xml -> createElement("time", $node -> getTime());
            $time->setAttribute("formatted", date("Y-m-d\TH:i:s\Z", $node->getTime()));

            $nodeEl->appendChild($time);
            $nodeEl->appendChild($vehicle);
            $nodeEl->appendChild($platform);
            $nodeEl->appendChild($station);

            $rootNode -> appendChild($nodeEl);
        }
        return $xml;
    }

}
?>
