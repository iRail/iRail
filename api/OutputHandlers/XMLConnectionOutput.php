<?php

/**
 * Description of XMLConnectionOutput
 *
 * @author pieterc
 */

include_once("ConnectionOutput.php");
class XMLConnectionOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header('Content-Type: text/xml');
        $xml = parent::buildXML($this->connections);
        echo $xml -> saveXML();
    }
    
}
?>
