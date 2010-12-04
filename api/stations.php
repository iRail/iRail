<?php

/*
  Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
  Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

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

  source available at http://github.com/Tuinslak/iRail
 */

/**
 * This is the API request handler
 */
include_once("DataStructs/StationsRequest.php");
include_once("APICall.php");
date_default_timezone_set("Europe/Brussels");

$lang = "EN";
$format = "xml";

//required vars, output error messages if empty
extract($_GET);

$request = new StationsRequest($lang, $format);
$call = new APICall("stations", $request);
$call->executeCall();

?>
