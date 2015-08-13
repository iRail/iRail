<?php
/* Copyright (C) 2010, 2011 by iRail vzw/asbl */
/*
  This file is part of iRail.

  iRail is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  iRail is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with iRail.  If not, see <http://www.gnu.org/licenses/>.

  http://project.irail.be - http://irail.be

  Source available at http://github.com/Tuinslak/iRail
 */


/**
 * This returns information about 1 specific station
 */
include_once '../vendor/autoload.php';
include_once("APICall.php");
include_once("requests/LiveboardRequest.php");
date_default_timezone_set("Europe/Brussels");
$call = new APICall("liveboard");
$call->executeCall();

