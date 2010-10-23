<?php
/**
 * Old fashioned scraper of railtime
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");
include_once("LiveboardInput.php");
include_once("DataStructs/Station.php");
include_once("DataStructs/TripNode.php");

class BRailLiveboardInput extends LiveboardInput{ 
    private $arrdep;
    private $name;
    protected function fetchData(Request $request) {
        include "getUA.php";
        $url="http://hari.b-rail.be/Hafas/bin/extxml.exe";
        $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
        $request_options = array(
                "referer" => "http://api.irail.be/",
                "timeout" => "30",
                "useragent" => $irailAgent,
        );
        $stationname = strtoupper($request -> getStation());
        include("includes/railtimeids.php");
        $rtid = $railtimeids[$stationname];
        $this -> arrdep = $request -> getArrdep();
        $this-> name = $request -> getStation();
        $scrapeUrl .= "?l=". $request -> getLang() . "&s=1&sid=". $rtid ."&tr=22:15-60&da=". substr($request -> getArrdep(), 0,1) ."&p=2";
        $post = http_post_data($scrapeUrl, "", $request_options) or die("");
        $body = http_parse_message($post)->body;

        return $body;
    }

    protected function transformData($serverData) {

        //echo $serverData;
        $name = $this -> name;
        $locationX = 0; //todo
        $locationY = 0;
        $station = new Station($name, $locationX, $locationY);
        $arrdep = $this -> arrdep;
        $nodes = array();
        preg_match_all("/<td valign=\"top\">(.*?)<\/td>/ism", $serverData, $matches);
        $i = 0;
        foreach($matches[1] as $td){
            if($i == 0){
                $i++;
                continue;
            }
//TODO:<a href="/mobile/SearchTrain.aspx?l=EN&s=1&tid=2043&da=D&p=2">22:20</a></td><td valign="top">&nbsp;<font color="Red">Changed</font>&nbsp;Charleroi-Sud&nbsp;<span>[IC2043&nbsp;Track&nbsp;<font color="Red">12</font>]</span>&nbsp;<img src="/mobile/images/Work.png" border="0" /><br/>
            $station = new Station($name, $locationX, $locationY);
            $vehicle = "ohjo";
            $unixtime = 0;
            $delay = 0;
            $platform = 1;
            $platformNormal = "yes";
            $nodes[$i-1] = new TripNode($platform, $delay, $unixtime, $station, $vehicle, $platformNormal);
            $i++;
        }

        $liveboard = new Liveboard($station, $arrdep, $nodes);
        return $liveboard;
    }

}
?>
