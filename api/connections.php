<?php

/**
 * This is the API request handler
 */

header('Content-Type: text/xml');

/*
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
echo "<?xml-stylesheet type=\"text/xsl\" href=\"xmlstylesheets/trains.xsl\" ?>";
*/

include("DataStructs/ConnectionRequest.php");
include("InputHandlers/BRailConnectionInput.php");
include("OutputHandlers/XMLConnectionOutput.php");


$date = "";
$time = "";
$results = "";
$lang = "";
$timeSel = "";
$typeOfTransport = "";
//required vars, output error messages if empty
extract($_GET);
//$from = $_GET["from"];
//$to = $_GET["to"];
//
////optional vars
//$date = $_GET["date"];
//$time = $_GET["time"];
//$results = $_GET["results"];
//$lang = $_GET["lang"];
//$timeSel = $_GET["timesel"];
//$typeOfTransport = $_GET["typeOfTransport"];



if($lang == "") {
    $lang = "EN";
}

if($timeSel == "") {
    $timeSel = "depart";
}

if($results == "" || $results > 6 || $results < 1) {
    $results = 6;
}

if($date == "") {
    $date = "20" . date("ymd");
}

if($time == "") {
    $time = date("H:i");
}

if($typeOfTransport == ""){
    $typeOfTransport = "train";
}
try{
    $request = new ConnectionRequest($from, $to, $time, $date, $timeSel, $results, $lang, $typeOfTransport);
    $input0 = new BRailConnectionInput();
    $output = new XMLConnectionOutput($input0 -> execute($request));
    $output -> printAll();
}catch(Exception $e){
    echo $e->getMessage();//error handling..
}


?>
