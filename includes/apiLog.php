<?php
/*  Copyright 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others

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

/*	db & columns info (from ~/includes/dbConfig.php)

	$api_host = "localhost";		// db host
	$api_user = "irail";			// db user
	$api_password = "password";		// passwd
	$api_database = "irail";		// db name
	$api_table = "apilog";			// table name

	$api_c1 = "id";					// unique ID
	$api_c2 = "time";				// request unix time
	$api_c3 = "useragent";			// UA, if any
	$api_c4 = "from";				// from station
	$api_c5 = "to";					// to station
	$api_c8 = "server";
*/

// API MySQL logging
// Documentation at http://project.irail.be/cgi-bin/trac.fcgi/wiki/ApiMySqlLogging

// API access logging to database

function writeLog($ua, $from, $to, $err, $ip) {
	// include vars
	include("dbConfig.php");

	// debug
	//echo $ua . " - " . $from . " - " . $to;

	// get time + date in rfc2822 format
	date_default_timezone_set('Europe/Brussels');
	$now = date('D, d M Y H:i:s');

	// checks
	if($from == "") {
		$from = "EMPTY";
	}

	if($to == "") {
		$to = "EMPTY";
	}

	if ($ua == "") {
		$ua = "-";
	}

	$from = str_replace("'", "", $from);
	$to = str_replace("'", "", $to);
	$err = str_replace("'", "", $err);

	$from = str_replace("\"", "", $from);
	$to = str_replace("\"", "", $to);
	$err = str_replace("\"", "", $err);

	// connect to db
	try {
		mysql_pconnect($api_host, $api_user, $api_password);
		mysql_select_db($api_database);
	}
	catch (Exception $e) {
		echo "Error connecting to the database.";
	}

	// insert in db
	try {
		$query = "INSERT INTO $api_table ($api_c2, $api_c3, $api_c4, $api_c5, $api_c6, $api_c7, $api_c8) VALUES('$now', '$ua', '$from', '$to', '$err', '$ip', '$api_server_name')";
		$result = mysql_query($query);
	}
	catch (Exception $e) {
		echo "Error writing to the database.";
	}

	// debug
	//echo $query . "<br />" . $result . "<br />" . mysql_error();
}
?>
