<?php
/**
 * Description of MobileWebOutput
 *
 * @author pieterc
 */

include("MobileWebOutput.php");
class MobileWebOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        $this->printHeader();
        

    }

    private function printHeader() {
        echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<title>iRail -'. $connections[0] -> getDepart() -> getStation() -> getName() . ' to ' . $connections[0] -> getArrival() -> getStation() -> getName() .'</title>
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
}
?>
