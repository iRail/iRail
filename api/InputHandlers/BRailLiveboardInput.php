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

class BRailLiveboardInput extends LiveboardInput {

    // private $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
    private $arrdep;
    private $name;

    protected function fetchData(Request $request) {
        include "getUA.php";
        $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
        $request_options = array(
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        );
        $stationname = strtoupper($request->getStation());
        include("includes/railtimeids.php");
        $rtid = $railtimeids[$stationname];
        $this->arrdep = $request->getArrdep();
        $this->name = $request->getStation();

        $scrapeUrl .= "?l=" . $request->getLang() . "&s=1&sid=" . $rtid . "&tr=22:15-60&da=" . substr($request->getArrdep(), 0, 1) . "&p=2";
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
//TODO:<a href="/mobile/SearchTrain.aspx?l=EN&s=1&tid=2043&da=D&p=2">22:20</a></td><td valign="top">&nbsp;<font color="Red">Changed</font>&nbsp;Charleroi-Sud&nbsp;<span>[IC2043&nbsp;Track&nbsp;<font color="Red">12</font>]</span>&nbsp;<img src="/mobile/images/Work.png" border="0" /><br/>
//TODO:<font color="Red" face="Arial" size="-1"> +5'</font><font face="Arial" size="-1">&nbsp;Oostende&nbsp;<span>[IC531]</span>&nbsp;<img src="/mobile/images/Work.png" border="0">

            preg_match("/2\">(..:..)<\/a>/ism", $td, $m);
            if (!isset($m[0])) {
                continue;
            }
            $date = 20 . date("ymd");
            $unixtime = Input::transformTime("00d" . $m[1] . ":00", $date);
            //echo $td;
            preg_match("/\+(.+?)'/ism", $td, $m);
            $delay = 0;
            if (isset($m[0]) && $m[1] != "") {
                $delay = $m[1] * 60;
            }
            preg_match("/&nbsp;(.*?)&nbsp;<span>\[(.*?)[\]&].*?(\d+)/ism", $td, $m);

            $stationNode = $this->getStation($m[1]);
            $vehicle = $this->getVehicle($m[2]);
            $platform = $m[3];
            $platformNormal = "yes";
            $nodes[$i - 1] = new TripNode($platform, $delay, $unixtime, $stationNode, $vehicle, $platformNormal);
            $i++;
        }

        $liveboard = new Liveboard($station, $arrdep, $nodes);
        return $liveboard;
    }

    protected function getVehicle($id){
        return new Train($id, "BE", "NMBS");
    }

    /**
     * This function will use approximate string matching to determine what station we're looking for
     * @param string $name
     */
    private function getStation($name1) {
        include("includes/coordinates.php");
        $name1 = strtoupper($name1);
        $max = 0;
        $match = "";
        $coord = "";
        foreach($coordinates as $name2 => $coordin){
            similar_text($name1, $name2, $score);
            if($score > $max){
                //DBG: echo $score . " " . $name1 . " " . $name2 . "\n";
                $max = $score;
                $match = $name2;
                $coord = $coordin;
            }
        }
        $coords = split(" ", $coord);
        return new Station($match, $coords[0],$coords[1], "");
    }

}
?>
