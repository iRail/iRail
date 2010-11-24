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
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
        //this function builds a DOM XML-tree
        //$xml = parent::buildXML($this->stations);
        $jsonstring= $this->jsonStations();
        echo ($callback ? $callback . '(' : '') . $jsonstring  . ($callback ? ')' : '');
    }

    private function jsonStations(){
        $output = "station:[";
        foreach($this->stations as $station){
            $output .= "{".$this->jsonStation($station) . "},";
        }
        return $output;
    }

    private function jsonStation($station){
        return '"id":"' . $station -> getId() . ',"name":"' . $station->getName() . '","locationX":"' . $station->getX() . '","locationY":"' . $station->getY(). '"';
    }

}
?>
