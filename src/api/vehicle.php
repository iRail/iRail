<?php
/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * This page will return information about a specific vehicle.
 */

namespace Irail\api;

use Irail\api\data\NMBS\VehicleDatasource;

require_once '../../vendor/autoload.php';

date_default_timezone_set('Europe/Brussels');
$call = new APICall(VehicleDatasource::class);
$call->executeCall();
