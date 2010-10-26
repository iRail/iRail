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
            $coords = explode(" ", $coord);
            $locationX = $coords[1];
            $locationY = $coords[0];
            $stations[$i] = new Station($station, $locationX, $locationY);
            $i++;
        }
        return $stations;
    }

}
?>
