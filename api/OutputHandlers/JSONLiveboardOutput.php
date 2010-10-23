<?php
/**
 * Description of JSONLiveboardOutput
 *
 * @author pieterc
 */
include_once("LiveboardOutput.php");
class JSONLiveboardOutput extends LiveboardOutput {
    private $liveboard;

    function __construct($l) {
        $this -> liveboard = $l;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header("Content-Type: application/json");
        $xml = parent::buildXML($this->liveboard);
        //yes this may cause some overhead, but it's the easiest way to implement this for now.
        echo json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
    }
}
?>
