<?php

/*
 * This page will return information about a specific vehicle.
 */
include_once("DataStructs/VehicleRequest.php");
include_once("APICall.php");
date_default_timezone_set("Europe/Brussels");

$format = "xml";
$lang = "EN";
$vehicleId = "";
extract($_GET);
if (!isset($id)) {
    $e = new Exception("You should give us a vehicleId", 1);
    $eh = new ErrorHandler($e, $format);
    $eh->printError();
} else {
    $request = new VehicleRequest($id, $lang, $format);
    $call = new APICall("vehicles", $request);
    $call->executeCall();
}
?>
