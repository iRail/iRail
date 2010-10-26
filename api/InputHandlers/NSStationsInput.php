<?php
/**
 * Description of NSStationsInput
 *
 * @author pieterc
 */
include_once("BRailStationsInput.php");
class NSStationsInput extends BRailStationsInput{

    protected function fetchData(Request $request) {
        include("includes/nscoordinates.php");
        return $coordinates;
    }

}
?>
