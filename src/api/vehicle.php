<?php
/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * This page will return information about a specific vehicle.
 */

namespace Irail\api;

require_once '../../vendor/autoload.php';

date_default_timezone_set('Europe/Brussels');
$call = new APICall('VehicleInformation');
$call->executeCall();
