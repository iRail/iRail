<?php
/**
 * Description of NSStationsInput
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");
include_once("StationsInput.php");
include_once("DataStructs/Station.php");
include_once("BRailStationsInput.php");
class NSStationsInput extends BRailStationsInput{

    protected function fetchData(Request $request) {
        include("includes/nscoordinates.php");
        return $coordinates;
    }

}
?>
