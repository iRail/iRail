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

    public function fetchData(Request $request) {
        include "getUA.php";
        $this->request = $request;
        $scrapeUrl = "http://www.railtime.be/mobile/SearchStation.aspx";
        $request_options = array(
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        );
        $station = $this->getStation($request->getStation());
        $this->arrdep = $request->getArrdep();
        $this->name = $request->getStation();
        $time = $request->getTime();
        $scrapeUrl .= "?l=" . $request->getLang() . "&tr=". $time . "-60&s=1&sid=" . $station->getAdditional() . "&da=" . substr($request->getArrdep(), 0, 1) . "&p=2";
        $post = http_post_data($scrapeUrl, "", $request_options) or die("");
        $body = http_parse_message($post)->body;

        return $body;
    }

    public function transformData($serverData) {
        $station = $this->getStation($this->name);
        $arrdep = $this->arrdep;
        $nodes = array();
        preg_match_all("/<tr>(.*?)<\/tr>/ism", $serverData, $m);
        $i = 0;
        //for each row
        foreach ($m[1] as $tr) {
            preg_match("/<td valign=\"top\">(.*?)<\/td><td valign=\"top\">(.*?)<\/td>/ism", $tr, $m2);
            //$m2[1] has: time
            //$m2[2] has delay, stationname & platform

            //GET TIME:
            preg_match("/(\d\d:\d\d)/", $m2[1], $t);
            $time = "00d" . $t[1] . ":00";
            $unixtime = parent::transformTime($time,"20".date("ymd"));

            //GET DELAY
            $delay = 0;
            preg_match("/\+(\d+)'/", $m2[2], $d);
            if(isset($d[1])){
                $delay = $d[1] * 60;
            }

            //GET STATION
            preg_match("/.*&nbsp;(.*?)&nbsp;<span/",$m2[2],$st);
            //echo $st[1] . "\n";
            $stationNode = parent::getStation($st[1]);

            //GET VEHICLE AND PLATFORM
            $platform = "NA";
            $platformNormal = true;
            preg_match("/\[(.*?)(&nbsp;.*?)?\]/",$m2[2],$veh);
            $vehicle = $this->getVehicle($veh[1]);
            if(isset($veh[2])){
                if(preg_match("/<[bf].*?>(.*?)<\/.*?>/", $veh[2], $p)){
                    $platform = $p[1];
                    $platformNormal = false;
                }else{
                    //echo $veh[2] . "\n";
                    preg_match("/&nbsp;.*?&nbsp;(.*)/", $veh[2], $p2);
                    if(isset($p2[1])){
                        $platform = $p2[1];
                    }
                }
            }

            preg_match("/\[.*?&nbsp;.*?/", $m2[2], $pl);

            $nodes[$i - 1] = new TripNode($platform, $delay, $unixtime, $stationNode, $vehicle, $platformNormal);
            $i++;
        }

        $liveboard = new Liveboard($station, $arrdep, $nodes);
        return $liveboard;
    }

    protected function getVehicle($id) {
        return new Train($id, "BE", "NMBS");
    }

}
?>
