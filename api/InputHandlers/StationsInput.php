<?php

/**
 * This class has to be a provider for all Stations available
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:api/:../includes:includes");
include_once("Input.php");
include_once("DataStructs/Station.php");

class StationsInput extends Input {

    protected function fetchData(Request $request, bool $international = false) {
        $country = strtoupper($request->getCountry());
        $lang = strtoupper($request->getLang());
        $stations = array();
        $count = 0;
        $pre = "";
        //yes, I hate this dirty hack. It's here to provide for the iRail client as well as for the API.
        //It needs to disapear if we rewrite the mobile client
        if (sizeof(explode("api", $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"])) > 1) {
            $pre = "../";
        }
        $file = $pre . "stationlists/" . $country . ".csv";
        if (!file_exists($file)) {
            $file = $pre . "stationlists/" . $country . "_EN.csv";
        }
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (isset($data[4])) {
                    $stations[$count] = new Station($data[1], $data[3], $data[2], $data[0], $data[4]);
                } else {
                    $stations[$count] = new Station($data[1], $data[3], $data[2], $data[0]);
                }
                $count++;
            }
            fclose($handle);
        }
        if ($international) {
            $fileINT = $pre . "stationlists/INT.csv";
            if (($handle = fopen($fileINT, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    if (isset($data[4])) {
                        $stations[$count] = new Station($data[1], $data[3], $data[2], $data[0], $data[4]);
                    } else {
                        $stations[$count] = new Station($data[1], $data[3], $data[2], $data[0]);
                    }
                    $count++;
                }
                fclose($handle);
            }
        }
        return $stations;
    }

    protected function transformData($serverData) {
        return $serverData;
    }

    public function generate_js_array($request) {
        $stations = $this->fetchData($request);
        $output = '';
        foreach ($stations as $i => $station) {
            $output .= '"' . $station->getName() . '",';
        }
        $output = rtrim($output, ',');
        return $output;
    }

    public function getStationFromId($id, $request) {
        $stations = $this->execute($request);
        foreach ($stations as $station) {
            if ($station->getId() == $id) {
                return $station;
            }
        }
        throw new Exception("No station for station id found (getStationFromId)", 3);
    }

}
?>
