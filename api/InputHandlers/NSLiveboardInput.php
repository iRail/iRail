<?php

/**
 * Old fashioned scraper of railtime
 *
 * @author pieterc
 */
include_once("BRailLiveboardInput.php");

class NSLiveboardInput extends BRailLiveboardInput {
    protected function getVehicle($id){
        return new Train($id, "NL", "NS");
    }
}
?>
