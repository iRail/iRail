<?php

/*
 * This page will return information about a specific vehicle.
 */
include_once("DataStructs/VehicleRequest.php");
include_once("APICall.php");

$format = "xml";
$lang = "EN";
$vehicleId = "";
extract($_GET);
if (vehicleId == "") {
    $e = new Exception("You should give us a vehicleId", 1);
    $eh = new ErrorHandler($e, $format);
    $eh->printError();
} else {
    $request = new VehicleRequest($vehicleId, $lang, $format);
    $call = new APICall("vehicles", $request);
    $call->executeCall();
}
?>
