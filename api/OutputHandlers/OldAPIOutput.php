<?php


/**
 * OldAPIOutput is a class to allow old API output to be used. Especially by BeTrains
 *
 * @author pieterc
 */
include_once("Output.php");
class OldAPIOutput implements Output {

    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printError($errorCode, $msg){
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("error", $msg);
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));
        $rootNode -> setAttribute("code", $errorCode);
        $xml->appendChild($rootNode);
    }

    public function printAll() {
        date_default_timezone_set("Europe/Brussels");
        $xml = new DOMDocument("1.0", "UTF-8");
        //<?xml-stylesheet type="text/xsl" href="xmlstylesheets/trains.xsl"
        $xmlstylesheet = $xml -> createProcessingInstruction("xml-stylesheet", 'type="text/xsl" href="xmlstylesheets/trains.xsl"');
        $xml -> appendChild($xmlstylesheet);

        $rootNode = $xml -> createElement("connections");
        $rootNode -> setAttribute("version", "0.2");
        $rootNode -> setAttribute("timestamp", date("U"));

        $xml -> appendChild($rootNode);
        $conId = 0;
        foreach($this -> connections as $c) {
            /* @var $c Connection */
            $connection = $xml -> createElement("connection");
            $connection -> setAttribute("id", $conId);
            //DEPART
            $departure = $xml -> createElement("departure");
            $station = $xml -> createElement("station", $c -> getDepart() -> getStation() -> getName());
            $station -> setAttribute("location", $c -> getDepart() -> getStation() -> getY() . " " .$c -> getDepart() -> getStation() -> getX()  );
            $time0 = $xml -> createElement("time", date("H:i",$c -> getDepart() -> getTime()));
            $date0 = $xml -> createElement("date", date("dmy",$c -> getDepart() -> getTime()));
            $departure -> appendChild($time0);
            $departure -> appendChild($date0);
            $departure -> appendChild($station);
            $connection -> appendChild($departure);

            //ARRIVAL
            $arrival = $xml -> createElement("arrival");
            $station = $xml -> createElement("station", $c -> getArrival() -> getStation() -> getName());
            $station -> setAttribute("location", $c -> getArrival() -> getStation() -> getY() . " " .$c -> getArrival() -> getStation() -> getX()  );
            $time0 = $xml -> createElement("time", date("H:i",$c -> getArrival() -> getTime()));
            $date0 = $xml -> createElement("date", date("dmy",$c -> getArrival() -> getTime()));
            $arrival -> appendChild($time0);
            $arrival -> appendChild($date0);
            $arrival -> appendChild($station);
            $connection -> appendChild($arrival);

            $delay = $xml -> createElement("delay", "0");
            if($c -> getDepart() -> getDelay() > 0) {
                $delay = $xml -> createElement("delay", "1");
            }

            $connection -> appendChild($delay);

            //OTHER
            $minutes = $c -> getDuration()/60 % 60;
            $hours = floor($c -> getDuration() / 3600);
            if($minutes < 10){
                $minutes = "0" . $minutes;
            }

            $duration = $xml -> createElement("duration", $hours. ":" . $minutes);

            $connection -> appendChild($duration);

            //TRAINS
            $trains = $xml -> createElement("trains");
            
            foreach($c -> getVias() as $v) {
                $train = $xml -> createElement("train", $v -> getVehicle() -> getInternalId());
                $trains -> appendChild($train);
            }
            
            $trainArr = $xml -> createElement("train", $c -> getArrival()-> getVehicle() -> getInternalId());
            $trains -> appendChild($trainArr);

            $connection -> appendChild($trains);

            $rootNode -> appendChild($connection);
            $conId ++;
        }
        echo $xml -> saveXML();
    }

}
?>
