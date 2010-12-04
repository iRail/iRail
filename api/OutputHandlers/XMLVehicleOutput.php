<?php

/**
 * Description of XMLVehicleOutput
 *
 * @author pieterc
 */
include_once("OutputHandlers/VehicleOutput.php");
class XMLVehicleOutput extends VehicleOutput{

    private $route;

    public function __construct($lang, $route) {
        parent::__construct($lang);
        $this->route = $route;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header('Content-Type: text/xml');
        $xml = parent::buildXML($this->route);
        echo $xml -> saveXML();
    }
}
?>
