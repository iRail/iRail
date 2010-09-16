<?php
/**
 * Description of BRailConnectionInput
 *
 * @author pieterc
 */

include("Input.php");
include("DataStructs/Connection.php");
include("DataStructs/Station.php");
include("DataStructs/TripNode.php");
include("DataStructs/Via.php");
include("DataStructs/Vehicle.php");


class BRailConnectionInput extends Input {


    /**
     * This function will get the data from nmbs we need.
     * @param Request $request
     * @return <type>
     */
    protected function fetchData(Request $request) {
        include "../includes/getUA.php";
        $url="http://hari.b-rail.be/Hafas/bin/extxml.exe";
        $request_options = array(
                "referer" => "http://irail.be/",
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
<ReqT time="'. $request -> getTime() .'" date="'. $request -> getDate() .'" a="0">
</ReqT>
<RFlags b="0" f="'. $request -> getResults() .'">
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
        foreach($xml -> ConRes -> ConnectionList -> Connection as $conn){

            $platform0 = $conn -> Overview ->Departure -> BasicStop -> Dep -> Platform -> Text;
            $delay0 = 0;//NYImplemented
            $unixtime0 = $this->transformTime($conn -> Overview -> Departure -> BasicStop -> Dep -> Time ,$conn -> Overview -> Date);
            $nameStation0 = $conn -> Overview -> Departure -> BasicStop -> Station['name'];
            $locationX0 = $conn -> Overview -> Departure -> BasicStop -> Station['x'];
            $locationY0 = $conn -> Overview -> Departure -> BasicStop -> Station['y'];
            $station0 = new Station($nameStation0, $locationX0, $locationY0);
            $vehicle0 = new Vehicle("nyimplemented");


            $platform1 = $conn -> Overview ->Arrival   -> BasicStop -> Arr -> Platform -> Text;
            $delay1 = 0;//NYImplemented
            $unixtime1 = $this->transformTime($conn -> Overview -> Arrival ->BasicStop -> Arr -> Time, $conn -> Overview -> Date);
            $nameStation1 = $conn -> Overview -> Arrival -> BasicStop -> Station['name'];
            $locationX1 = $conn -> Overview -> Arrival -> BasicStop -> Station['x'];
            $locationY1 = $conn -> Overview -> Arrival -> BasicStop -> Station['y'];
            $station1 = new Station($nameStation1, $locationX1, $locationY1);
            $vehicle1 = new Vehicle("nyimplemented");

            $depart = new TripNode($platform0, $delay0, $unixtime0, $station0, $vehicle0);
            $arrival = new TripNode($platform1, $delay1, $unixtime1, $station1, $vehicle1);

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
     */
    private function transformTime($time, $date){
        //I solved it with substrings. DateTime class is such a Pain In The Ass.
        //No sh!t.
        $dayoffset = intval(substr($time,0,2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        $year = intval(substr($date, 0,4));
        $month = intval(substr($date, 4,2));
        $day = intval(substr($date,6,2));
        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
    }

    function transformDuration($time) {
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        return $hour*3600 + $minute * 60 + $second;
    }

}
?>
