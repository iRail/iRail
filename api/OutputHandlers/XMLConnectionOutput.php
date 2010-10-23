<?php

/**
 * Description of XMLConnectionOutput
 *
 * @author pieterc
 */

include_once("Output.php");
class XMLConnectionOutput implements Output {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header('Content-Type: text/xml');
        $xml = $this-> buildXML($this->connections);
        echo $xml -> saveXML();
    }
    
    public static function buildXML($connectionsarray){
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml -> createElement("connections");
        $rootNode -> setAttribute("version", "1.0");
        $rootNode -> setAttribute("timestamp", date("U"));

        $xml -> appendChild($rootNode);
        $conId = 0;
        foreach($connectionsarray as $c) {
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
            if(sizeof($c-> getVias()) > 0 ) {
                $vias = $xml -> createElement("vias");
                $vias -> setAttribute("number", sizeof($c -> getVias()));
                $k = 0;
                foreach($c -> getVias() as $v) {
                    $via = $xml -> createElement("via");
                    $via -> setAttribute("id", $k);

                    $arrivalv = $xml -> createElement("arrival");
                    $platformv = $xml -> createElement("platform", $v -> getArrivalPlatform());
                    $timev = $xml -> createElement("time", date("U", $v -> getArrivalTime()));
                    $timev -> setAttribute("formatted", date("Y-m-d\TH:i\Z", $v -> getArrivalTime()));
                    $arrivalv-> appendChild($platformv);
                    $arrivalv -> appendChild($timev);

                    $departv = $xml -> createElement("departure");
                    $platformv = $xml -> createElement("platform", $v -> getDepartPlatform());
                    $timev = $xml -> createElement("time", date("U", $v -> getDepartTime()));
                    $timev -> setAttribute("formatted", date("Y-m-d\TH:i\Z", $v -> getDepartTime()));
                    $departv-> appendChild($platformv);
                    $departv -> appendChild($timev);

                    $timeBetween = $xml -> createElement("timeBetween", $v-> getTimeBetween());

                    $stationv = $xml->createElement("station", $v -> getStation() -> getName());
                    $stationv -> setAttribute("location", $v ->getStation() -> getY() . " " .$v ->getStation() -> getX() );

                    $vehiclev = $xml -> createElement("vehicle", $v -> getVehicle() -> getId());

                    $via -> appendChild($arrivalv);
                    $via -> appendChild($departv);
                    $via -> appendChild($timeBetween);
                    $via -> appendChild($stationv);
                    $via -> appendChild($vehiclev);

                    $vias -> appendChild($via);
                    $k++;
                }
                $connection -> appendChild($vias);
            }

            //OTHER
            $duration = $xml -> createElement("duration", $c -> getDuration());
            //$duration -> setAttribute("formatted", date("H:i:s", $c -> getDuration())); // date functions are crazy in PHP... Don't understand this one...

            $connection -> appendChild($duration);

            $rootNode -> appendChild($connection);
            $conId ++;
        }
        return $xml;
    }
}
?>
