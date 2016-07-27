<?php
/** Copyright (C) 2011 by iRail vzw/asbl
 * fillDataRoot will fill the entire dataroot with a liveboard for a specific station.
 */
include_once 'data/NMBS/tools.php';
include_once 'data/NMBS/stations.php';
include_once 'occupancy/OccupancyOperations.php';

class liveboard
{
    /**
     * @param $dataroot
     * @param $request
     * @throws Exception
     */
    public static function fillDataRoot($dataroot, $request)
    {
        $stationr = $request->getStation();
        $dataroot->station = new \stdClass();
        
        try {
            $dataroot->station = stations::getStationFromName($stationr, strtolower($request->getLang()));
        } catch (Exception $e) {
            throw new Exception('Could not find station ' . $stationr, 404);
        }
        $request->setStation($dataroot->station);
        
        if (strtoupper(substr($request->getArrdep(), 0, 1)) == 'A') {
            $html = self::fetchData($dataroot->station, $request->getTime(), $request->getLang(), 'arr');
            $dataroot->arrival = self::parseData($html, $request->getTime(), $request->getLang(), $request->isFast(), $request->getAlerts(), null);
        } elseif (strtoupper(substr($request->getArrdep(), 0, 1)) == 'D') {
            $html = self::fetchData($dataroot->station, $request->getTime(), $request->getLang(), 'dep');
            $dataroot->departure = self::parseData($html, $request->getTime(), $request->getLang(), $request->isFast(), $request->getAlerts(), $dataroot->station);
        } else {
            throw new Exception('Not a good timeSel value: try ARR or DEP', 400);
        }
    }

    /**
     * @param $station
     * @param $time
     * @param $lang
     * @param $timeSel
     * @return string
     */
    private static function fetchData($station, $time, $lang, $timeSel)
    {
        include '../includes/getUA.php';
        $request_options = [
            'referer' => 'http://api.irail.be/',
            'timeout' => '30',
            'useragent' => $irailAgent,
        ];
        $body = '';
        /*
          For example, run this in command line:

          $ curl "http://hari.b-rail.be/Hafas/bin/stboard.exe/en?start=yes&time=15%3a12&date=01.09.2011&inputTripelId=A=1@O=@X=@Y=@U=80@L=008892007@B=1@p=@&maxJourneys=50&boardType=dep&hcount=1&htype=NokiaC7-00%2f022.014%2fsw_platform%3dS60%3bsw_platform_version%3d5.2%3bjava_build_version%3d2.2.54&L=vs_java3&productsFilter=00010000001111"
        */
        //$scrapeUrl = "http://hari.b-rail.be/Hafas/bin/stboard.exe/";
        $scrapeUrl = 'http://www.belgianrail.be/jp/sncb-nmbs-routeplanner/stboard.exe/';
        $scrapeUrl .= $lang.'?start=yes';
        $hafasid = $station->getHID();
        //important TODO: date parameters - parse from URI first
        $scrapeUrl .= '&time='.$time.'&date='.date('d').'.'.date('m').'.'.date('Y').'&inputTripelId='.urlencode('A=1@O=@X=@Y=@U=80@L='.$hafasid.'@B=1@p=@').'&maxJourneys=50&boardType='.$timeSel.'&hcount=1&htype=NokiaC7-00%2f022.014%2fsw_platform%3dS60%3bsw_platform_version%3d5.2%3bjava_build_version%3d2.2.54&L=vs_java3&productsFilter=0111111000000000';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scrapeUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $request_options['useragent']);
        curl_setopt($ch, CURLOPT_REFERER, $request_options['referer']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $request_options['timeout']);
        $response = curl_exec($ch);
        curl_close($ch);
        //Strangly, the response didn't have a root-tag
        return '<xml>'.$response.'</xml>';
    }

    /**
     * @param $xml
     * @param $time
     * @param $lang
     * @param bool $fast
     * @param bool $showAlerts
     * @return array
     */
    private static function parseData($xml, $time, $lang, $fast = false, $showAlerts = false, $station)
    {
        //clean XML
        if (class_exists('tidy', false)) {
            $tidy = new tidy();
            $tidy->parseString($xml, ['input-xml' => true, 'output-xml' => true], 'utf8');
            $tidy->cleanRepair();
            $xml = $tidy;
        }
        $data = new SimpleXMLElement($xml);
        $hour = substr($time, 0, 2);
        $data = $data->StationTable;

        //<Journey fpTime="08:36" fpDate="03/09/11" delay="-"
        //platform="2" targetLoc="Gent-Dampoort [B]" prod="L    758#L"
        //dir="Eeklo [B]" is_reachable="0" />

        $nodes = [];
        $i = 0;

        $hour = substr($time, 0, 2);
        $hour_ = substr((string) $data->Journey[0]['fpTime'], 0, 2);
        if ($hour_ != '23' && $hour == '23') {
            $hour_ = 24;
        }

        $minutes = substr($time, 3, 2);
        $minutes_ = substr((string) $data->Journey[0]['fpTime'], 3, 2);

        $departureStation = null;

        if (!is_null($station)) {
            $departureStation = "http://irail.be/stations/NMBS/" . substr($station->id, strrpos($station->id, '.') + 1);
            ;
        }

        while (isset($data->Journey[$i]) && ($hour_ - $hour) * 60 + ($minutes_ - $minutes) <= 60) {
            $journey = $data->Journey[$i];

            $left = 0;
            $canceled = false;
            $delay = (string) $journey['delay'];
            if ($delay == '-') {
                $delay = '0';
            }
            
            if ($delay == 'cancel') {
                $delay = 0;
                $canceled = true;
            }
            
            if (isset($journey['e_delay'])) {
                $delay = $journey['e_delay'] * 60;
            }
            
            preg_match("/\+\s*(\d+)/", $delay, $d);
            if (isset($d[1])) {
                $delay = $d[1] * 60;
            }
            preg_match("/\+\s*(\d):(\d+)/", $delay, $d);
            if (isset($d[1])) {
                $delay = $d[1] * 3600 + $d[2] * 60;
            }

            $platform = '?'; // Indicate to end user platform is unknown
            $platformNormal = true;
            if (isset($journey['platform'])) {
                $platform = (string) $journey['platform'];
            }
            
            if (isset($journey['newpl'])) {
                $platform = (string) $journey['newpl'];
                $platformNormal = false;
            }
            
            $time = '00d'.(string) $journey['fpTime'].':00';
            preg_match("/(..)\/(..)\/(..)/si", (string) $journey['fpDate'], $dayxplode);
            $dateparam = '20'.$dayxplode[3].$dayxplode[2].$dayxplode[1];

            $unixtime = tools::transformtime($time, $dateparam);

            if ($fast) {
                $stationNode = new Station();
                $stationNode->name = (string) $journey['targetLoc'];
                $stationNode->name = str_replace(' [B]', '', $stationNode->name);
                $stationNode->name = str_replace(' [NMBS/SNCB]', '', $stationNode->name);
            } else {
                $stationNode = stations::getStationFromName($journey['targetLoc'], $lang);
            }

            $veh = $journey['hafasname'];
            $veh = substr($veh, 0, 8);
            $veh = str_replace(' ', '', $veh);
            $vehicle = 'http://irail.be/vehicle/'.$veh;

            $date = date('Ymd');

            $nodes[$i] = new DepartureArrival();
            $nodes[$i]->delay = $delay;
            $nodes[$i]->station = $stationNode;
            $nodes[$i]->time = $unixtime;
            $nodes[$i]->vehicle = new \stdClass();
            $nodes[$i]->vehicle->name = $veh;
            $nodes[$i]->vehicle->{'@id'} = $vehicle;
            $nodes[$i]->platform = new Platform();
            $nodes[$i]->platform->name = $platform;
            $nodes[$i]->platform->normal = $platformNormal;
            $nodes[$i]->canceled = $canceled;
            $nodes[$i]->left = $left;
            $nodes[$i]->departureConnection = 'http://irail.be/connections/' . substr(basename($station->{'@id'}), 2) . '/' . date('Ymd', $unixtime) . '/' . basename($vehicle);

            if (!is_null($departureStation)) {
                try {
                    $occupancy = OccupancyOperations::getOccupancyURI($vehicle, $departureStation, $date);

                    // Check if the MongoDB module is set up. If not, the occupancy score will not be returned.
                    if (!is_null($occupancy)) {
                        $nodes[$i]->occupancy = new \stdClass();
                        $nodes[$i]->occupancy->name = basename($occupancy);
                        $nodes[$i]->occupancy->{'@id'} = $occupancy;
                    }
                } catch (Exception $e) {
                    // Database connection failed, in the future a warning could be given to the owner of iRail
                    $departureStation == null;
                }
            }

            $hour_ = substr((string) $data->Journey[$i]['fpTime'], 0, 2);
            if ($hour_ != '23' && $hour == '23') {
                $hour_ = 24;
            }
            $minutes_ = substr((string) $data->Journey[$i]['fpTime'], 3, 2);
            
            // Alerts
            if ($showAlerts && isset($journey->HIMMessage)) {
                $alerts = [];
                $himmessage = $journey->HIMMessage;
                for ($a = 0; $a < count($himmessage); $a++) {
                    $alert = new Alert();
                    $alert->header = trim($himmessage[$a]['header']);
                    $alert->description = trim($himmessage[$a]['lead']);
                    array_push($alerts, $alert);
                }
                $nodes[$i]->alert = $alerts;
            }

            $i++;
        }

        return array_merge($nodes); //array merge reindexes the array
    }
};
