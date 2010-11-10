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
    echo "<error>You should provide us with a vehicle id please.</error>";
} else {
    $request = new VehicleRequest($vehicleId, $lang, $format);
    $call = new APICall("vehicles", $request);
    $call->executeCall();
}
?>
