<?php

/**
 * Description of JSONConnectionOutput
 *
 * @author pieterc
 */

include_once("Output.php");
include_once("XMLConnectionOutput.php");
class JSONConnectionOutput implements Output {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        //header("Content-Type: application/json");
        $xml = XMLConnectionOutput::buildXML($this->connections);
        echo json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
    }
}
?>
