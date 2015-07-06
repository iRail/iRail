<?php
  /* Copyright (C) 2011 by iRail vzw/asbl
   *
   * This page will return information about a specific vehicle.
   */
require_once "requests/VehicleinformationRequest.php";
require_once "APICall.php";
date_default_timezone_set("Europe/Brussels");
$call = new APICall("vehicleinformation");
$call->executeCall();
