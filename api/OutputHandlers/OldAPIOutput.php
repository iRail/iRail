<?php


/**
 * OldAPIOutput is a class to allow old API output to be used. Especially by BeTrains
 *
 * @author pieterc
 */
include("Output.php");
class OldAPIOutput extends Output {

    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("Europe/Brussels");
        $xml = new DOMDocument("1.0", "UTF-8");
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
            $departure -> setAttribute("delay",  $c ->getDepart() -> getDelay());

            $station = $xml -> createElement("station", $c -> getDepart() -> getStation() -> getName());
            $station -> setAttribute("location", $c -> getDepart() -> getStation() -> getY() . " " .$c -> getDepart() -> getStation() -> getX()  );

            $time0 = $xml -> createElement("time", $c -> getDepart() -> getTime());
            $time0 -> setAttribute("formatted", date("Y-m-d\TH:i\Z", $c -> getDepart() -> getTime() ) );

            $date0 = $xml -> createElement("time", $c -> getDepart() -> getTime());
            $date0 -> setAttribute("formatted", date("Y-m-d\TH:i\Z", $c -> getDepart() -> getTime() ) );

            $arrival -> appendChild($vehicle);
            $arrival-> appendChild($platform);
            $arrival -> appendChild($time1);
            $arrival-> appendChild($station);
            $connection -> appendChild($arrival);

            //OTHER
            $duration = $xml -> createElement("duration", $c -> getDuration());

            $connection -> appendChild($duration);

            $rootNode -> appendChild($connection);
            $conId ++;
        }
        echo $xml -> saveXML();
    }

}
?>
