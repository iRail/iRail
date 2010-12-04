<?php
/**
 * Description of BRailConnectionInput
 *
 * @author pieterc
 */
include_once("ConnectionInput.php");
include_once("Connection.php");
include_once("Station.php");
include_once("Train.php");
include_once("TripNode.php");
include_once("Via.php");
include_once("BRailConnectionInput.php");

class NSConnectionInput extends BRailConnectionInput {

    /**
     * This function will get the data from nmbs we need.
     * @param Request $request
     * @return <type>
     */
    public function fetchData(Request $request) {
        $this->request = $request;
        include "getUA.php";
        $url="http://hafas.bene-system.com/bin/query.exe";
        $request_options = array(
                "referer" => "http://api.irail.nl/",
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
        }else if (strcmp($request -> getTimeSel(), "arrive") == 0) {
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

    protected function newTrain($id){
        return new Train($id, "NL", "NS");
    }

}
?>
