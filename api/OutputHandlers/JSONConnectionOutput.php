<?php

/**
 * Description of JSONConnectionOutput
 *
 * @author pieterc
 */

include_once("ConnectionOutput.php");
class JSONConnectionOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header("Content-Type: application/json");
        $xml = parent::buildXML($this->connections);
        //yes this may cause some overhead, but it's the easiest way to implement this for now.
        echo json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
    }
}
?>
