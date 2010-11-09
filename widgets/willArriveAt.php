<?php
/*  Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
    Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

    This file is part of iRail.
 *
 * This is an example widget. You can reuse the code to make your own widgets.
*/

// National query page

chdir("../");

include_once("api/DataStructs/ConnectionRequest.php");

include_once("includes/apiLog.php");

include_once("api/OutputHandlers/ConnectionOutput.php");
class WidgetOutput extends ConnectionOutput{
    private $connection;
    private $name;
    public function __construct($connection, $name = "") {
        $this -> connection = $connection;
        $this -> name = $name;
    }

    public function printAll() {
        echo '<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
        <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
        <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
        <title>iRail - Mobile</title>
    </head>
    <body>
       <div data-role="page" id="config">
            <div data-role="header">
                <h1> iRail </h1>
                <a href="" onclick="javascript: window.location.reload()" data-icon="refresh" class="ui-btn-right">refresh</a>
            </div>
            <div data-role="content">
                '. $this->name .' will arrive at <h1>'.$this-> connection -> getArrival() -> getStation() -> getName().'</h1>
                    in <strong>'.$this->calculateMinutes() . '</strong> minutes!
            </div>
            <div data-role="footer">
                <a href="http://iRail.be">iRail</a>
                <a href="../about.php?output=jQueryMobile">About</a>
                <a href="http://project.iRail.be" target="_blank">Dev</a>
            </div>

    </body>
</html>

';
    }

    private function calculateMinutes(){
        //date_default_timezone_set("Europe/Brussels");
        //echo date("ymd - H:i",$this -> connection -> getArrival() -> getTime() + $this-> connection -> getDepart() -> getDelay());
        return floor(($this -> connection -> getArrival() -> getTime() + $this-> connection -> getDepart() -> getDelay() - date("U"))/60);
    }

}

$lang = "";
$timesel = "";
$name = "";
$language = "EN";
$date = "";
$time="";
extract($_COOKIE);
extract($_GET);
$lang = $language;
// if bad stations, go back
if(!isset($_GET["from"]) || !isset($_GET["to"]) || $from == $to) {
	header('Location: ..');
}
if(!isset($_POST["timesel"])){
    $timesel = "depart";
}
$results = 1;
$typeOfTransport = "train";
if($date == "") {
    $date = date("dmy");
}

//TODO: move this to constructor of ConnectionRequest

//reform date to needed train structure
preg_match("/(..)(..)(..)/si",$date, $m);
$date = "20" . $m[3] . $m[2] . $m[1];

if($time == "") {
    $time = date("Hi");
}

//reform time to wanted structure
preg_match("/(..)(..)/si",$time, $m);
$time = $m[1] . ":" . $m[2];


try {
    $request = new ConnectionRequest($from, $to, $time, $date, $timesel, $results, $lang, $typeOfTransport);
    $input = $request ->getInput();
    $connections = $input -> execute($request);
    $output = new WidgetOutput($connections[0], $name);
    $output -> printAll();

    // Log request to database
    writeLog("willArriveAtWidget - " . $_SERVER['HTTP_USER_AGENT'], $connections[0] -> getDepart() -> getStation() -> getName(), $connections[0] -> getArrival() -> getStation() -> getName(), "none (iRail.be)", $_SERVER['REMOTE_ADDR']);
}catch(Exception $e) {
    writeLog("willArriveAtWidget - " . $_SERVER['HTTP_USER_AGENT'],"", "", "Error on willArriveAtWidget: " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
    //header('Location: ../noresults');
    echo $e->getMessage(); //error handling..
}

?>
