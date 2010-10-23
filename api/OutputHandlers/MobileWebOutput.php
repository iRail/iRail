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

    private $page = array(
            "title" => "iRail.be",
            "strike" => ""
    );

    //Todo: this should be moved into the connections datastruct
    private $strikes = array("18/10/2010", "19/10/2010");

    function __construct($c) {
        $this -> connections = $c;

        $this->page["GoogleAnalytics"] = file_get_contents("includes/googleAnalytics.php") ;
        $this->page["footer"] = file_get_contents("includes/footer.php");
        if(array_search(date("d/m/Y",$this->connections[0] -> getDepart()->getTime()), $this->strikes) !== false) {
        																// what is substrike? empty div?
            $this->page["strike"] = '<div id="strike">{i18n_strike}</div><div id="substrike"></div>';
        }

    }

    protected function loadContent() {
        $this->page["from"] = $this-> connections[0] -> getDepart() -> getStation() -> getName();
        $this->page["to"] = $this-> connections[0] -> getArrival() -> getStation() -> getName();
        $this->page["date"] = date("d/m/Y", $this->connections[0] -> getDepart() -> getTime());
        $this->page["connections"] = $this->getConnectionsOutput();
        foreach($this ->page as $tag => $value) {
            $this -> content = str_ireplace("{".$tag."}", $value, $this->content);
        }
    }

    public function printAll() {
        if(sizeof($this->connections) == 0){
            header('Location: noresults');
        }
        $this-> buildPage("Results.tpl");
    }

    private function getConnectionsOutput() {
        date_default_timezone_set("Europe/Brussels");
        $output= "";
        $index = 0;
        foreach($this->connections as $con) {
            $output .= "<tr class=\"color". $index%2 ."\">";
            $output .= "<td>" . date("H:i", $con -> getDepart() -> getTime()) . "<br/>". date("H:i", $con -> getArrival() -> getTime()) ."</td>";

            $minutes = $con -> getDuration()/60 % 60;
            $hours = floor($con -> getDuration() / 3600);
            if($minutes < 10) {
                $minutes = "0" . $minutes;
            }
            $output .= "<td>" . $hours. ":" . $minutes ."</td>";

            // color delayed minutes in red, else print /
            $tmp_delay = $con -> getDepart() -> getDelay()/60;

            if($tmp_delay == 0) {
                $delay_output = "/";
            }else {
                $delay_output = "<span style=\"color:red\">" . $tmp_delay . "</span>m";
            }

            $output .= "<td>" . $delay_output . "</td>";

            $output .= "<td>" . $con -> getDepart() -> getPlatform() . "</td>";

            $output .= "<td>" . sizeof($con -> getVias()) . "</td>";

            $output .= "<td>" . $this -> getTrains($con) ."</td>";

            $output .= "</tr>";
            $index ++;
        }
        return $output;
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
