<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 *
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station
 *
 * @package data/NMBS
 */
include_once("data/NMBS/tools.php");
include_once("data/NMBS/stations.php");
class liveboard{
    public static function fillDataRoot($dataroot,$request){
        $arr = explode(".",$request->getStation());
        $stationr=$request->getStation();
        if(sizeof($arr)>1){
            $stationr=$arr[2];
	}
        $dataroot->station = stations::getStationFromName($stationr, $request->getLang());
        if(strtoupper(substr($request->getArrdep(), 0, 1)) == "A"){
            $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"arr");
            $dataroot->arrival = liveboard::parseData($html, $request->getTime(), $request->getLang(),$request->isFast());
        }else if(strtoupper(substr($request->getArrdep(), 0, 1)) == "D"){
            $html = liveboard::fetchData($dataroot->station, $request->getTime(), $request->getLang(),"dep");
            $dataroot->departure = liveboard::parseData($html, $request->getTime(), $request->getLang(), $request->isFast());
        }
        else{
            throw new Exception("Not a good timeSel value: try ARR or DEP", 300);
        }
    }

    private static function fetchData($station, $time, $lang, $timeSel){
        include "../includes/getUA.php";
        $request_options = array(
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        );
        $body = "";
        /*
          For example, run this in command line:

          $ curl "http://hari.b-rail.be/Hafas/bin/stboard.exe/en?start=yes&time=15%3a12&date=01.09.2011&inputTripelId=A=1@O=@X=@Y=@U=80@L=008892007@B=1@p=@&maxJourneys=50&boardType=dep&hcount=1&htype=NokiaC7-00%2f022.014%2fsw_platform%3dS60%3bsw_platform_version%3d5.2%3bjava_build_version%3d2.2.54&L=vs_java3&productsFilter=00010000001111"
        */
        //$scrapeUrl = "http://hari.b-rail.be/Hafas/bin/stboard.exe/";
	$scrapeUrl = "http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/stboard.exe/";
        $scrapeUrl .= $lang ."?start=yes";
        $hafasid = $station->getHID();
        //important TODO: date parameters - parse from URI first
        $scrapeUrl .= "&time=" . $time ."&date=". date("d") . "." . date("m") .".". date("Y") ."&inputTripelId=". urlencode("A=1@O=@X=@Y=@U=80@L=". $hafasid ."@B=1@p=@") . "&maxJourneys=50&boardType=" . $timeSel . "&hcount=1&htype=NokiaC7-00%2f022.014%2fsw_platform%3dS60%3bsw_platform_version%3d5.2%3bjava_build_version%3d2.2.54&L=vs_java3&productsFilter=0111111000000000";

        $post = http_post_data($scrapeUrl, "", $request_options) || die("");
        $body .= http_parse_message($post)->body;
        //Strangly, the response didn't have a root-tag
        return "<xml>" . $body . "</xml>";

    }

    private static function parseData($xml,$time,$lang, $fast = false){
	//clean XML
        if(class_exists("tidy",false)) {
                $tidy = new tidy();
                $tidy->parseString($xml, array('input-xml' => true,'output-xml' => true),'utf8');
                $tidy->cleanRepair();
                $xml = $tidy;
	}
        $data = new SimpleXMLElement($xml);
        $hour = substr($time, 0,2);
	$data = $data->StationTable;
//<Journey fpTime="08:36" fpDate="03/09/11" delay="-"
//platform="2" targetLoc="Gent-Dampoort [B]" prod="L    758#L"
//dir="Eeklo [B]" is_reachable="0" />

	$nodes = array();
        $i = 0;

        $hour = substr($time,0,2);
        $hour_ = substr((string)$data->Journey[0]["fpTime"],0,2);
        if($hour_ != "23" && $hour == "23") $hour_ = 24;

        $minutes = substr($time,3,2);
        $minutes_ = substr((string)$data->Journey[0]["fpTime"],3,2);

        while(isset($data->Journey[$i]) &&($hour_-$hour)*60 + ($minutes_ - $minutes) <= 60){
            $journey = $data->Journey[$i] ;

            $left = 0;
            $delay = (string)$journey["delay"];
            if($delay == "-"){
                $delay="0";
            }

            $platform = "";
            if(isset($journey["platform"])){
                $platform = (string)$journey["platform"];
            }
            $time = "00d" . (string)$journey["fpTime"] . ":00";
            preg_match("/(..)\/(..)\/(..)/si",(string)$journey["fpDate"],$dayxplode);
	    $dateparam = "20" . $dayxplode[3].$dayxplode[2].$dayxplode[1];

            $unixtime = tools::transformtime($time,$dateparam);

            //GET DELAY
            preg_match("/\+\s*(\d+)/", $delay, $d);
            if(isset($d[1])){
                $delay = $d[1] * 60;
            }
            preg_match("/\+\s*(\d):(\d+)/", $delay, $d);
            if(isset($d[1])){
                $delay = $d[1] * 3600 + $d[2]*60;
            }

            if($fast){
                $stationNode = new Station();
                $stationNode->name = (string) $journey["dir"];
                $stationNode->name = str_replace(" [B]", "", $stationNode->name);
                $stationNode->name = str_replace(" [NMBS/SNCB]", "", $stationNode->name);
            }else{
                $stationNode = stations::getStationFromName($journey["dir"], $lang);
            }

            //GET VEHICLE AND PLATFORM

            $platformNormal = true;
	    $veh = $journey["hafasname"];
            $veh = substr($veh,0,8);
            $veh = str_replace(" ","",$veh);
            $vehicle = "BE.NMBS." . $veh;

	    $nodes[$i] = new DepartureArrival();
	    $nodes[$i]->delay= $delay;
	    $nodes[$i]->station= $stationNode;
	    $nodes[$i]->time= $unixtime;
	    $nodes[$i]->vehicle = $vehicle;
	    $nodes[$i]->platform = new Platform();
	    $nodes[$i]->platform->name = $platform;
	    $nodes[$i]->platform->normal = $platformNormal;
	    $nodes[$i]->left = $left;
            $hour_ = substr((string)$data->Journey[$i]["fpTime"],0,2);
            if($hour_ != "23" && $hour == "23") $hour_ = 24;
            $minutes_ = substr((string)$data->Journey[$i]["fpTime"],3,2);
            $i++;
	}
        return array_merge($nodes);//array merge reindexes the array
    }
};


?>
