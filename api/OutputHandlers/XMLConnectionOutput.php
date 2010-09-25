<?php

/**
 * Description of XMLConnectionOutput
 *
 * @author pieterc
 */

include("ConnectionOutput.php");
class XMLConnectionOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll(){
        date_default_timezone_set("UTC");
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml -> createElement("connections");
        $rootNode -> setAttribute("version", "1.0");
        $rootNode -> setAttribute("timestamp", date("U"));

        $xml -> appendChild($rootNode);
        $conId = 0;
        foreach($this -> connections as $c){
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

            $platform = $xml -> createElement("platform", $c -> getDepart() -> getPlatform());
            $platform -> setAttribute("normal", $c->getDepart()-> normalPlatform());

            $vehicle = $xml -> createElement("vehicle", $c -> getDepart() ->getVehicle() -> getId());

            $departure -> appendChild($vehicle);
            $departure-> appendChild($platform);
            $departure -> appendChild($time0);
            $departure -> appendChild($station);
            $connection -> appendChild($departure);

            //ARRIVAL
            $arrival = $xml -> createElement("arrival");
            $arrival -> setAttribute("delay",  $c -> getArrival() ->getDelay());

            $station = $xml -> createElement("station", $c -> getArrival() -> getStation() -> getName());
            $station -> setAttribute("location", $c -> getArrival() -> getStation() -> getY() . " " .$c -> getArrival() -> getStation() -> getX()  );

            $time1 = $xml -> createElement("time", $c -> getArrival() -> getTime());
            //iso8601 standard for time: 2010-09-17T09:12Z
            $time1 -> setAttribute("formatted", date("Y-m-d\TH:i\Z", $c -> getArrival() -> getTime() ) );

            $platform = $xml -> createElement("platform", $c -> getArrival() -> getPlatform());
            $platform -> setAttribute("normal", $c->getArrival()-> normalPlatform());

            $vehicle = $xml -> createElement("vehicle", $c -> getArrival() ->getVehicle() -> getId());

            $arrival -> appendChild($vehicle);
            $arrival-> appendChild($platform);
            $arrival -> appendChild($time1);
            $arrival-> appendChild($station);
            $connection -> appendChild($arrival);

            //VIAS
            //NY Implemented

            //OTHER
            $duration = $xml -> createElement("duration", $c -> getDuration());
            //$duration -> setAttribute("formatted", date("H:i:s", $c -> getDuration())); // date functions are crazy in PHP... Don't understand this one...

            $connection -> appendChild($duration);

            $rootNode -> appendChild($connection);
            $conId ++;
        }
        echo $xml -> saveXML();
    }
}
?>
