<?php

/**
 * Description of JSONStationsOutput
 *
 * @author pieterc
 */
include_once("StationsOutput.php");

class JSONStationsOutput extends StationsOutput {

    private $stations;

    public function __construct($s) {
        $this->stations = $s;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header('Content-Type: application/json');
        //this function builds a DOM XML-tree
        $xml = parent::buildXML($this->stations);
        echo json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
    }

}
?>
