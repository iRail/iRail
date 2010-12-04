<?php

/**
 * Description of BRailConnectionInput
 *
 * @author pieterc
 */
include_once("ConnectionInput.php");
include_once("Connection.php");
include_once("Station.php");
include_once("TripNode.php");
include_once("Via.php");
include_once("Train.php");

class BRailConnectionInput extends ConnectionInput {

    /**
     * This function will get the data from nmbs we need.
     * @param Request $request
     * @return <type>
     */
    public function fetchData(Request $request) {
        $this->request = $request;
        include "getUA.php";
        $url = "http://hari.b-rail.be/Hafas/bin/extxml.exe";
        $request_options = array(
            "referer" => "http://api.irail.be/",
            "timeout" => "30",
            "useragent" => $irailAgent,
        );

        //first request: Getting the id of the right stations
        $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="iRail API v1.0" lang="EN">
<LocValReq id="from" maxNr="1">
<ReqLoc match="' . $request->getFrom() . '" type="ST"/>
</LocValReq>
<LocValReq id="to" maxNr="1">
<ReqLoc match="' . $request->getTo() . '" type="ST"/>
</LocValReq>
</ReqC>';

        $post = http_post_data($url, $postdata, $request_options) or die("");
        $idbody = http_parse_message($post)->body;

        preg_match_all("/externalId=\"(.*?)\"/si", $idbody, $matches);
        $idfrom = $matches[1][0];
        $idto = $matches[1][1];

        //for now
        $trainsonly = "1111111111111111";
        $timeSel = 0;
        if ($request->getTimeSel() == "depart") {
            $timeSel = 0;
        } else if (strcmp($request->getTimeSel(), "arrive") == 0) {
            $timeSel = 1;
        }
        //now we're going to get the real data
        $postdata = '<?xml version="1.0 encoding="iso-8859-1"?>
<ReqC ver="1.1" prod="irail" lang="' . $request->getLang() . '">
<ConReq>
<Start min="0">
<Station externalId="' . $idfrom . '" distance="0">
</Station>
<Prod prod="' . $trainsonly . '">
</Prod>
</Start>
<Dest min="0">
<Station externalId="' . $idto . '" distance="0">
</Station>
</Dest>
<Via>
</Via>
<ReqT time="' . $request->getTime() . '" date="' . $request->getDate() . '" a="' . $timeSel . '">
</ReqT>
<RFlags b="' . $request->getResults() * $timeSel . '" f="' . $request->getResults() * -($timeSel - 1) . '">
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

    public function transformData($serverData) {
        $inp = new StationsInput();
        $stations = $inp->execute($this->request);
        $xml = new SimpleXMLElement($serverData);
        $connections = array();
        $i = 0;
        //DEBUG: echo $serverData ;
        if (isset($xml->ConRes->ConnectionList->Connection)) {
            foreach ($xml->ConRes->ConnectionList->Connection as $conn) {

                $platform0 = trim($conn->Overview->Departure->BasicStop->Dep->Platform->Text);

                $unixtime0 = Input::transformTime($conn->Overview->Departure->BasicStop->Dep->Time, $conn->Overview->Date);
                $nameStation0 = $conn->Overview->Departure->BasicStop->Station['name'];
                $station0 = parent::getStation($nameStation0);

                $platform1 = trim($conn->Overview->Arrival->BasicStop->Arr->Platform->Text);

                $unixtime1 = Input::transformTime($conn->Overview->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);
                $nameStation1 = $conn->Overview->Arrival->BasicStop->Station['name'];
                $station1 = parent::getStation($nameStation1);

                //Delay or other wrongish stuff
                $delay0 = 0;
                $delay1 = 0;
                $platformNormal0 = true;
                $platformNormal1 = true;
                if ($conn->RtStateList->RtState["value"] == "HAS_DELAYINFO") {

                    $delay0 = Input::transformTime($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Time, $conn->Overview->Date) - $unixtime0;
                    if ($delay0 < 0) {
                        $delay0 = 0;
                    }
                    //echo "delay: " .$conn->Overview -> Departure -> BasicStop -> StopPrognosis -> Dep -> Time . "\n";
                    $delay1 = Input::transformTime($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Time, $conn->Overview->Date) - $unixtime1;
                    if ($delay1 < 0) {
                        $delay1 = 0;
                    }

                    if (isset($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text)) {
                        $platform0 = trim($conn->Overview->Departure->BasicStop->StopPrognosis->Dep->Platform->Text);
                        $platformNormal0 = false;
                    }
                    if (isset($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text)) {
                        $platform1 = trim($conn->Overview->Arrival->BasicStop->StopPrognosis->Arr->Platform->Text);
                        $platformNormal1 = false;
                    }
                }
                $trains = array();
                $vias = array();
                $j = 0;
                $connectionindex = 0;
                //yay for spaghetti code.
                if (isset($conn->ConSectionList->ConSection)) {
                    foreach ($conn->ConSectionList->ConSection as $connsection) {

                        if (isset($connsection->Journey->JourneyAttributeList->JourneyAttribute)) {
                            foreach ($connsection->Journey->JourneyAttributeList->JourneyAttribute as $att) {
                                if ($att->Attribute["type"] == "NAME") {
                                    $trains[$j] = str_replace(" ", "", $att->Attribute->AttributeVariant->Text);
                                    $j++;
                                    break;
                                }
                            }

                            if ($conn->Overview->Transfers > 0 && strcmp($connsection->Arrival->BasicStop->Station['name'], $conn->Overview->Arrival->BasicStop->Station['name']) != 0) {
                                //current index for the train: j-1
                                $departDelay = 0; //Todo: NYImplemented
                                $connarray = $conn->ConSectionList->ConSection;
                                $departTime = Input::transformTime($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Time, $conn->Overview->Date);
                                $departPlatform = trim($connarray[$connectionindex + 1]->Departure->BasicStop->Dep->Platform->Text);
                                $arrivalTime = Input::transformTime($connsection->Arrival->BasicStop->Arr->Time, $conn->Overview->Date);
                                $arrivalPlatform = trim($connsection->Arrival->BasicStop->Arr->Platform->Text);
                                $arrivalDelay = 0; //Todo: NYImplemented
                                $stationv = parent::getStation($connsection->Arrival->BasicStop->Station["name"]);
                                $vehiclev = $this->newTrain($trains[$j - 1]);
                                $vias[$connectionindex] = new Via($vehiclev, $stationv, $arrivalTime, $arrivalPlatform, $departTime, $departPlatform, $arrivalDelay, $departDelay);

                                $connectionindex++;
                            }
                        }
                    }
                }
                $vehicle0 = $this->newTrain($trains[0]);
                $vehicle1 = $this->newTrain($trains[sizeof($trains) - 1]);
                $depart = new TripNode($platform0, $delay0, $unixtime0, $station0, $vehicle0, $platformNormal0);
                $arrival = new TripNode($platform1, $delay1, $unixtime1, $station1, $vehicle1, $platformNormal1);



                $duration = Input::transformDuration($conn->Overview->Duration->Time);

                $connections[$i] = $i;
                $connections[$i] = new Connection($depart, $arrival, $vias, $duration);
                $i++;
            }
        } else {
            throw new Exception("We're sorry, we could not retrieve the correct data from our sources",2);
        }

        return $connections;
    }

    protected function newTrain($id) {
        return new Train($id, "BE", "NMBS");
    }

}
?>
