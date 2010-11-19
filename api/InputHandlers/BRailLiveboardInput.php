<?php

/**
 * Old fashioned scraper of railtime
 *
 * @author pieterc
 */
include_once("LiveboardInput.php");
include_once("DataStructs/Station.php");
include_once("DataStructs/TripNode.php");
include_once("DataStructs/Train.php");
include_once("StationsInput.php");

class BRailLiveboardInput extends LiveboardInput {

    // private $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
    private $arrdep;
    private $name;

    protected function fetchData(Request $request) {
        include "getUA.php";
        $this->request = $request;
        $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
        $request_options = array(
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        );
        $stationname = strtoupper($request->getStation());
        include("includes/railtimeids.php");
        if (array_key_exists($stationname, $railtimeids)) {
            $rtid = $railtimeids[$stationname];
        } else {
            throw new Exception("Station not available for liveboard", 3);
        }
        $this->arrdep = $request->getArrdep();
        $this->name = $request->getStation();
        $scrapeUrl .= "?l=" . $request->getLang() . "&s=1&sid=" . $rtid . "&da=" . substr($request->getArrdep(), 0, 1) . "&p=2";
        $post = http_post_data($scrapeUrl, "", $request_options) or die("");
        $body = http_parse_message($post)->body;

        return $body;
    }

    protected function transformData($serverData) {
        //echo $serverData;
        $n = $this->name;
        $locationX = 0; //todo
        $locationY = 0;
        $station = $this->getStation($n);
        $arrdep = $this->arrdep;
        $nodes = array();
        preg_match_all("/<td valign=\"top\">(.*?<\/td>.*?)<\/td>/ism", $serverData, $m);
        $i = 0;
        foreach ($m[1] as $td) {
            //echo $td . "\n";
            if ($i == 0) {
                $i++;
                continue;
            }
//=2">22:20</a></td><td valign="top">&nbsp;<font color="Red">Changed</font>&nbsp;Charleroi-Sud&nbsp;<span>[IC2043&nbsp;Track&nbsp;<font color="Red">12</font>]</span>&nbsp;<img src="/mobile/images/Work.png" border="0" /><br/>
//TODO:<font color="Red" face="Arial" size="-1"> +5'</font><font face="Arial" size="-1">&nbsp;Oostende&nbsp;<span>[IC531]</span>&nbsp;<img src="/mobile/images/Work.png" border="0">
//=2">12:05</a></font></td><td valign="top"><font size="-1" face="Arial">&nbsp;Antwerpen-Centraal&nbsp;<span>[IC732&nbsp;Spoor&nbsp;2]</span></font></td>

            /*             * MATCH TIME* */
            preg_match("/2\">(..:..)<\/a>/ism", $td, $m);
            if (!isset($m[0])) {
                continue;
            }
            $date = 20 . date("ymd");
            $unixtime = Input::transformTime("00d" . $m[1] . ":00", $date);
            //echo $td;
            /*             * MATCH DELAY* */
            preg_match("/\+(.+?)'/ism", $td, $m);
            $delay = 0;
            if (isset($m[0]) && $m[1] != "") {
                $delay = $m[1] * 60;
            }
            /*             * MATCH STATIONNAME, VEHICLE and PLATFORM* */
            preg_match("/\">&nbsp;(.*?)&nbsp;<span>\[(.*?)[\]&].*?(\d+)\].*?<\/span>/ism", $td, $m);
            //echo $m[1] . "\n";
            if(isset($m[1])) {
                $stationNode = parent::getStation($m[1]);
                $vehicle = $this->getVehicle($m[2]);
                $platform = $m[3];
                $platformNormal = "yes";
                $nodes[$i - 1] = new TripNode($platform, $delay, $unixtime, $stationNode, $vehicle, $platformNormal);
                $i++;
            }
        }

        $liveboard = new Liveboard($station, $arrdep, $nodes);
        return $liveboard;
    }

    protected function getVehicle($id) {
        return new Train($id, "BE", "NMBS");
    }

}
?>
