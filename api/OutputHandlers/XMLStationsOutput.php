<?php

/**
 * Description of XMLStationsOutput
 *
 * @author pieterc
 */
include_once("StationsOutput.php");

class XMLStationsOutput extends StationsOutput {
    private $stations;
    function __construct($lang, $s) {
        parent::__construct($lang);
        $this->stations = $s;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header('Content-Type: text/xml');
        //this function builds a DOM XML-tree
        $xml = parent::buildXML($this->stations);
        
        echo $xml->saveXML();
    }
}
?>
