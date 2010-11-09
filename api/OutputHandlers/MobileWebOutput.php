<?php
/**
 * Description of MobileWebOutput
 *
 * @author pieterc
 */

include_once("Output.php");
include_once("Page.php");

class MobileWebOutput extends Page implements Output {
    private $connections;
    //Todo: this should be moved into the connections datastruct
    private $strikes = array("18/10/2010", "19/10/2010");

    function __construct($c) {
        $this->page = array(
            "title" => "iRail.be",
            "strike" => ""
        );
        $this -> connections = $c;

        $this->page["GoogleAnalytics"] = file_get_contents("includes/googleAnalytics.php") ;
        $this->page["footer"] = file_get_contents("includes/footer.php");
        if(array_search(date("d/m/Y",$this->connections[0] -> getDepart()->getTime()), $this->strikes) !== false) {
        																// what is substrike? empty div?
            $this->page["strike"] = '<div id="strike">{i18n_strike}</div><div id="substrike"></div>';
        }

    }

    public function printError($errorCode, $msg){
        header("location: noresults.");
    }

    public function printAll() {
        if(sizeof($this->connections) == 0){
            header('Location: noresults');
        }
        $this->page["from"] = $this-> connections[0] -> getDepart() -> getStation() -> getName();
        $this->page["to"] = $this-> connections[0] -> getArrival() -> getStation() -> getName();
        $this->page["date"] = date("d/m/Y", $this->connections[0] -> getDepart() -> getTime());
        $this->loops["connections"] =$this->getConnectionsOutput();
        $this-> buildPage("Results.tpl");
    }

    private function getConnectionsOutput() {
        date_default_timezone_set("Europe/Brussels");
        $conoutput = array();
        $index = 0;
        foreach($this->connections as $con) {
            $conoutput[$index]["colorindex"] = $index %2;
            $conoutput[$index]["departtime"] = date("H:i", $con -> getDepart() -> getTime());
            $conoutput[$index]["arrivaltime"] = date("H:i", $con -> getArrival() -> getTime());

            $minutes = $con -> getDuration()/60 % 60;
            $hours = floor($con -> getDuration() / 3600);
            if($minutes < 10) {
                $minutes = "0" . $minutes;
            }
            $conoutput[$index]["duration"] = $hours. ":" . $minutes;
            $conoutput[$index]["delayed"] =  "";
            if($con -> getDepart() -> getDelay() > 0){
                $conoutput[$index]["delayed"] = "delayed";
            }

            $conoutput[$index]["delay"] = $con -> getDepart() -> getDelay();
            $conoutput[$index]["platform"] = $con -> getDepart() -> getPlatform();
            $conoutput[$index]["transfers"] = sizeof($con -> getVias());

            //$output .= "<td>" . $this -> getTrains($con) ."</td>";

            //$output .= "</tr>";
            $index ++;
        }
        return $conoutput;
    }

    private function getTrains(Connection $con) {
        $out = "";
        foreach($con -> getVias() as $v) {
            $out .= $v -> getVehicle() -> getInternalId() . "<br/>";
        }
        $out .= $con -> getArrival() -> getVehicle() -> getInternalId();
        return $out;
    }
}
?>
