<?php

/**
 * Description of XMLLiveboardOutput
 *
 * @author pieterc
 */
include_once("LiveboardOutput.php");

class XMLLiveboardOutput extends LiveboardOutput {

    private $liveboard;

    function __construct($lang, $l) {
        parent::__construct($lang);
        $this->liveboard = $l;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header("Content-Type: text/xml");
        $xml = parent::buildXML($this->liveboard);
        echo $xml->saveXML();
    }

}
?>
