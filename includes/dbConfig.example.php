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

// API DB config

$api_host = "localhost";		// db host
$api_user = "irail";			// db user
$api_password = "passwd";		// passwd
$api_database = "irail";		// db name
$api_table = "apilog";			// table name

// database columns 

$api_c1 = "id";					// unique ID
$api_c2 = "time";				// request unix time
$api_c3 = "useragent";			// UA, if any
$api_c4 = "fromstation";		// from station
$api_c5 = "tostation";			// to station

?>