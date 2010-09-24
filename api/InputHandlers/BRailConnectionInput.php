<?php
/**
 * Description of BRailConnectionInput
 *
 * @author pieterc
 */

include("ConnectionInput.php");
include("DataStructs/Connection.php");
include("DataStructs/Station.php");
include("DataStructs/TripNode.php");
include("DataStructs/Via.php");
include("DataStructs/BTrain.php");


class BRailConnectionInput extends ConnectionInput {


    /**
     * This function will get the data from nmbs we need.
     * @param Request $request
     * @return <type>
     */
    protected function fetchData(Request $request) {
        include "../includes/getUA.php";
        $url="http://hari.b-rail.be/Hafas/bin/extxml.exe";
        $request_options = array(
                "referer" => "http://api.irail.be/",
                "timeout" => "30",
                "useragent" => $irailAgent,
        );


        //first request: Getting the id of the right stations
        $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail API v1.0" lang="EN">
<LocValReq id="from" maxNr="1">
<ReqLoc match="'. $request -> getFrom() .'" type="ST"/>
</LocValReq>
<LocValReq id="to" maxNr="1">
<ReqLoc match="'. $request -> getTo() .'" type="ST"/>
</LocValReq>
</ReqC>';

        $post = http_post_data($url, $postdata, $request_options) or die("");
        $idbody = http_parse_message($post)->body;

        preg_match_all("/externalId=\"(.*?)\"/si", $idbody,$matches);
        $idfrom = $matches[1][0];
        $idto = $matches[1][1];

        //for now
        $trainsonly = "1111111111111111";
        $timeSel = 0;
        if($request -> getTimeSel() == "depart") {
            $timeSel = 0;
        }else if ($request -> getTimeSel() == "arrive") {
            $timeSel = 1;
        }
        //now we're going to get the real data
        $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="irail" lang="'. $request -> getLang() .'">
<ConReq>
<Start min="0">
<Station externalId="'. $idfrom .'" distance="0">
</Station>
<Prod prod="'. $trainsonly .'">
</Prod>
</Start>
<Dest min="0">
<Station externalId="'. $idto .'" distance="0">
</Station>
</Dest>
<Via>
</Via>
<ReqT time="'. $request -> getTime() .'" date="'. $request -> getDate() .'" a="'. $timeSel  .'">
</ReqT>
<RFlags b="'. $request -> getResults() * $timeSel .'" f="'. $request -> getResults() * -($timeSel-1) .'">
</RFlags>
<GISParameters>
<Front>
</Front>
<Back>
</Back>
</GISParameters>
</ConReq>
</ReqC>';
        $post = http_post_data($url, $postdata, $request_options) or die("<br />NMBS/SNCB website timeout. Please <a href='..'>refresh</a>.");
        return http_parse_message($post)->body;
    }

    protected function transformData($serverData) {

        $xml = new SimpleXMLElement($serverData);
        $connections = array();
        $i = 0;
        //echo $serverData ;
        foreach($xml -> ConRes -> ConnectionList -> Connection as $conn) {

            $platform0 = $conn -> Overview ->Departure -> BasicStop -> Dep -> Platform -> Text;

            $unixtime0 = $this->transformTime($conn -> Overview -> Departure -> BasicStop -> Dep -> Time ,$conn -> Overview -> Date);
            $nameStation0 = $conn -> Overview -> Departure -> BasicStop -> Station['name'];
            $locationX0 = $conn -> Overview -> Departure -> BasicStop -> Station['x'];
            $locationY0 = $conn -> Overview -> Departure -> BasicStop -> Station['y'];
            $station0 = new Station($nameStation0, $locationX0, $locationY0);
            $vehicle0 = new BTrain("nyimplemented");


            $platform1 = $conn -> Overview ->Arrival   -> BasicStop -> Arr -> Platform -> Text;

            $unixtime1 = $this->transformTime($conn -> Overview -> Arrival ->BasicStop -> Arr -> Time, $conn -> Overview -> Date);
            $nameStation1 = $conn -> Overview -> Arrival -> BasicStop -> Station['name'];
            $locationX1 = $conn -> Overview -> Arrival -> BasicStop -> Station['x'];
            $locationY1 = $conn -> Overview -> Arrival -> BasicStop -> Station['y'];
            $station1 = new Station($nameStation1, $locationX1, $locationY1);
            $vehicle1 = new BTrain("nyimplemented");

            //Delay or other wrongish stuff
            $delay0 = 0;
            $delay1 = 0;
            $platformNormal0 = true;
            $platformNormal1 = true;
            if($conn -> RtStateList -> RtState["value"] == "HAS_DELAYINFO") {

                $delay0= $this->transformTime($conn -> Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Time, $conn -> Overview -> Date) - $unixtime0;
                if($delay0 < 0) {
                    $delay0 = 0;
                }
                //echo "delay: " .$conn->Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Time . "\n";
                $delay1= $this->transformTime($conn -> Overview -> Arrival -> BasicStop -> StopPrognosis -> Arr -> Time, $conn -> Overview -> Date) - $unixtime1;
                if($delay1 < 0) {
                    $delay1 = 0;
                }

                //TODO: doesn't work? Needs testing
                if(isset($conn -> Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Platform->Text)) {
                    $platform0 = $conn -> Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Platform -> Text;
                    $platformNormal0= false;
                }
                if(isset($conn -> Overview -> Arrival -> BasicStop -> StopPrognosis -> Arr -> Platform-> Text)) {
                    $platform1 = $conn -> Overview -> Arrival -> BasicStop -> StopPrognosis -> Arr -> Platform -> Text;
                    $platformNormal1 = false;
                }
            }
            $trains = array();
            $j = 0;
            //yay for bad code.
            if(sizeof($conn -> ConSectionList) > 0 ) {
                foreach($conn -> ConSectionList -> ConSection as $connsection) {
                    foreach($connsection -> Journey -> JourneyAttributeList -> JourneyAttribute as $att) {
                        if($att -> Attribute["type"] == "NAME") {
                            $trains[$j] = str_replace(" ", "", $att -> Attribute -> AttributeVariant -> Text);
                            $j++;
                            break;
                        }
                    }
                }
            }
            $vehicle0 = new BTrain($trains[0]);
            $vehicle1 = new BTrain($trains[sizeof($trains)-1]);

            $depart = new TripNode($platform0, $delay0, $unixtime0, $station0, $vehicle0, $platformNormal0);
            $arrival = new TripNode($platform1, $delay1, $unixtime1, $station1, $vehicle1, $platformNormal1);

            $vias = array();

            $duration = $this -> transformDuration($conn -> Overview -> Duration -> Time);

            $connections[$i] = $i;
            $connections[$i] = new Connection($depart, $arrival, $vias, $duration);
            $i++;
        }
        return $connections;
    }

    /**
     *
     * @param <type> $time -> in 00d15:24:00
     * @param <type> $date -> in 20100915
     * @return seconds since the Unix epoch
     *
     */
    private function transformTime($time, $date) {
        //I solved it with substrings. DateTime class is such a Pain In The Ass.
        date_default_timezone_set("Europe/Brussels");
        $dayoffset = intval(substr($time,0,2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        $year = intval(substr($date, 0,4));
        $month = intval(substr($date, 4,2));
        $day = intval(substr($date,6,2));
        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
    }
    /**
     * This function transforms the brail formatted timestring and reformats it to seconds
     * @param int $time
     * @return int Duration in seconds
     */
    private function transformDuration($time) {
        $days = intval(substr($time, 0,2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        return $days*24*3600 + $hour*3600 + $minute * 60 + $second;
    }

}
?>
