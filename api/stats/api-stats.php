<html><head><title>iRail API MySQL (request) stats</title></head><body>
<table><tr><th>id</th><th>time</th><th>browser</th><th>from</th><th>to</th><th>errors</th><th>ip</th></tr>
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

// "Public" API stats page
// to prevent giving MySQL access to *

// include vars
include("../../includes/dbConfig.php");

	try {
		mysql_pconnect($api_host, $api_user, $api_password);
		mysql_select_db($api_database);
		$query = "SELECT $api_c1, $api_c2, $api_c3, $api_c4, $api_c5, $api_c6, $api_c7 FROM $api_table ORDER BY $api_c1 DESC";
		$result = mysql_query($query);
	}
	catch (Exception $e) {
		echo "Error connecting to the database.";
	}
	
	while($row = mysql_fetch_object($result)) {
		echo "<tr>";
		echo "<td>" . $row->$api_c1 . "</td>";
		echo "<td>" . $row->$api_c2 . "</td>";
		echo "<td>" . $row->$api_c3 . "</td>";
		echo "<td>" . $row->$api_c4 . "</td>";
		echo "<td>" . $row->$api_c5 . "</td>";
		echo "<td>" . $row->$api_c6 . "</td>";
		echo "<td>" . $row->$api_c7 . "</td>";
		echo "</tr>";
	}
?>
</table>
</body>
</html>