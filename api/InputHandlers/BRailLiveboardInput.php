<?php
/**
 * Description of BRailLiveboardInput
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");
include_once("LiveboardInput.php");
class BRailLiveboardInput extends LiveboardInput{
    private $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
    public function __construct() {

    }

    protected function fetchData(Request $request) {
        include "getUA.php";
        $url="http://hari.b-rail.be/Hafas/bin/extxml.exe";
        $request_options = array(
                "referer" => "http://api.irail.be/",
                "timeout" => "30",
                "useragent" => $irailAgent,
        );


    }

    protected function transformData($serverData) {

    }

}
?>
