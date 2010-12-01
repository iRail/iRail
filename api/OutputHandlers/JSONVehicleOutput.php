<?php

/**
 * Description of JSONVehicleOutput
 *
 * @author pieterc
 */
include_once("OutputHandlers/VehicleOutput.php");

class JSONVehicleOutput extends VehicleOutput {

    private $route;

    public function __construct($lang, $route) {
        parent::__construct($lang);
        $this->route = $route;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
        //this function builds a DOM XML-tree
        $xml = parent::buildXML($this->route);
        $jsonstring = json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
        $jsonstring = preg_replace('/"@attributes":{(.*?)}/sm ', "\\1", $jsonstring);
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? ')' : '');
    }

}
?>
