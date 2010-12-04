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

    public function fetchData(Request $request, $international = false) {
        $country = strtoupper($request->getCountry());
        $lang = strtoupper($request->getLang());
        $stations = array();
        $pre = "";
        //yes, I hate this dirty hack. It's here to provide for the iRail client as well as for the API.
        //It needs to disapear if we rewrite the mobile client
        if (sizeof(explode("api", $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"])) > 1) {
            $pre = "../";
        }
        $file = $pre . "stationlists/" . $country . ".csv";
        if (!file_exists($file)) {
            throw(new Exception("Your country is not supported yet", 3));
        }
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (!isset($stations[$data[0]])) {
                    $stations[$data[0]] = new Station($data[0], $data[3], $data[2], $data[4]);
                }
                $language = trim($data[5]);
                if ($language == '*') {
                    $stations[$data[0]]->addName("NL", $data[1]);
                    $stations[$data[0]]->addName("DE", $data[1]);
                    $stations[$data[0]]->addName("FR", $data[1]);
                    $stations[$data[0]]->addName("EN", $data[1]);
                }
                $stations[$data[0]]->addName($language,$data[1]);
            }
            fclose($handle);
        }
        if ($international) {
            $fileINT = $pre . "stationlists/INT.csv";
            if (($handle = fopen($fileINT, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    if (!isset($stations[$data[0]])) {
                        $stations[$data[0]] = new Station($data[0], $data[3], $data[2], $data[4]);
                    }
                    $language = trim($data[5]);
                    if ($language == '*') {
                        $stations[$data[0]]->addName("NL", $data[1]);
                        $stations[$data[0]]->addName("DE", $data[1]);
                        $stations[$data[0]]->addName("FR", $data[1]);
                        $stations[$data[0]]->addName("EN", $data[1]);
                    }
                    $stations[$data[0]]->addName($language,$data[1]);
                }
                fclose($handle);
            }
        }
        return $stations;
    }

    public function transformData($serverData) {
        return $serverData;
    }

    public function generate_js_array($request) {
        $stations = $this->fetchData($request);
        $output = '';
        foreach ($stations as $i => $station) {
            if(isset($_COOKIE['language'])){
                $output .= '"' . $station->getName($request->getLang()) . '",';
            }else{
                $previousname = array();
                foreach($station -> getNames() as $name){
                    if(in_array($name,$previousname)){
                        $output .=  '"' . $name. '",';
                        $previousname[sizeof($previousname)] = $name;
                    }
                }
            }
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
