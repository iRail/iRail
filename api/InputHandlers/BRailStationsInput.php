<?php
/**
 * Description of BRailStationsInput
 *
 * @author pieterc
 */
include_once("StationsInput.php");
include_once("DataStructs/Station.php");
class BRailStationsInput extends StationsInput{

    protected function fetchData(Request $request) {
        include("includes/coordinates.php");
        return $coordinates;
    }

    protected function transformData($serverData) {
        $stations = array();
        $i = 0;
        foreach($serverData as $station => $coord){
            preg_match("/(.*?) (.*?)/", $coord, $matches);
            $locationX = $matches[2];
            $locationY = $matches[1];
            $stations[$i] = new Station($station, $locationX, $locationY);
            $i++;
        }
        return $stations;
    }

}
?>
