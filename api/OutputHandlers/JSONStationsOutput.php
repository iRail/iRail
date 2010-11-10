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
        header('Content-Type: application/json;charset=UTF-8');
        //this function builds a DOM XML-tree
        $xml = parent::buildXML($this->stations);
        $callback = isset($_GET['callback'] && ctype_alnum($_GET['callback'])) ? $_GET['callback'] : false;
        echo ($callback ? $callback . '(' : '') . json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA)) . ($callback ? $callback . '(' : '');
    }

}
?>
