<?php
/**
 * Description of MobileWebOutput
 *
 * @author pieterc
 */

include("ConnectionOutput.php");

class MobileWebOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        $this->printHeader();
        $this->printBody();
    }

    private function printHeader() {
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<title>iRail - '. $this->connections[0] -> getDepart() -> getStation() -> getName() . ' to ' . $this->connections[0] -> getArrival() -> getStation() -> getName() .'</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<link href="css/query.css" rel="stylesheet" type="text/css" />
<link rel="apple-touch-icon" href="./img/irail.png" />
<link rel="shortcut icon" type="image/x-icon" href="./img/favicon.ico">
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<script type="application/x-javascript">
addEventListener(\'load\', function() { setTimeout(hideAddressBar, 0); }, false)
function hideAddressBar() { window.scrollTo(0, 1); }
</script>
</head><body>';
    }

    private function printBody() {
// language settings
$lang = $_COOKIE["language"];
switch($lang) {
    case "EN":
    	$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_map = "Map:";
        break;
    case "NL":
    	$txt_from = "Van:";
        $txt_to = "Naar:";
        $txt_date = "Datum:";
        $txt_map = "Kaart:";
        break;
    case "FR":
    	$txt_from = "De:";
        $txt_to = "Vers:";
        $txt_date = "Date:";
        $txt_map = "Carte:";
        break;
    case "DE":
    	$txt_from = "Von:";
        $txt_to = "Nach:";
        $txt_date = "Datum:";
        $txt_map = "Karte:";
        break;
    default:
    	$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_map = "Map:";
        break;
}
//
        $con = $this->connections[0];
	echo '<body>
<!-- compass image by Yusuke Kamiyamane, Creative Commons (Attribution 3.0 Unported) -->
<table>
<tr><td>
'. $txt_map .' <a href="http://maps.google.be/?saddr=Station'. $con -> getDepart() -> getStation() -> getName().'&daddr=Station '. $con -> getArrival() -> getStation() -> getName() . '" target="_blank"><img border="0" class="icon" src="img/map.png" width="14" height="14" alt="Local Map" /></a>
</td><td> '. $txt_date .' '. date("d/m/y", $con -> getDepart() -> getTime()) .'
</td></tr>
<tr><td> '. $txt_from .'<b> '.$con -> getDepart() -> getStation() -> getName() .'</b></td>
<td> '. $txt_to .'<b> '. $con -> getArrival() -> getStation() -> getName() . '</b></td></tr></table>
<table align="left" cellpadding="0" cellspacing="1" bgcolor="FFFFFF" summary="Train Info">
<tr>
<th>Time </th>
<th>Duration </th>
<th>Delay</th>
<th>Platform</th>
<th>Transfers</th>
<th>Transportation</th>
</tr>
'. $this->getConnectionsOutput();
        echo "<tr><td colspan=\"8\"><center><form name=\"return\" method=\"post\" action=\"national\"><input type=\"submit\" name=\"submit\" value=\"Back\"></center><br /></td></tr>";
        echo "</table>";
        echo '</body>
        </html>';
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

            $output .= "<td>" . $con ->getDepart() -> getDelay()/60 . "m</td>";

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
