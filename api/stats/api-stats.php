<html><head><title>iRail API MySQL (request) stats</title>
	<link href="api_stats.css" rel="stylesheet" type="text/css" />
	<link rel="apple-touch-icon" href="http://irail.be/apple-touch-icon.png" /></style>
	<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico"/>
</head><body><center>
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

use Dotenv\Dotenv;

// "Public" API stats page
// to prevent giving MySQL access to *

// vars
$limit = 250;

$dotenv = new Dotenv(dirname(__DIR__));
$dotenv->load();

$s = $_REQUEST['s'];

if (empty($s)) {
    $s = 0;
}

$count = 1 + $s;

    try {
        mysql_pconnect($_ENV['apiHost'], $_ENV['apiUser'], $_ENV['apiPassword']);
        mysql_select_db($api_database);
        $query = 'SELECT COUNT('.$_ENV['column1'].') FROM '.$_ENV['apiTable'].'';
        $result = mysql_query($query);
        $numrows = mysql_result($result, 0);

        $dbColumns = [
            $_ENV['column1'], $_ENV['column2'], $_ENV['column3'], $_ENV['column4'],
            $_ENV['column5'], $_ENV['column6'], $_ENV['column7'], $_ENV['column8'],
        ];

        $query = "SELECT $dbColumns FROM $api_table ORDER BY $api_c1 DESC LIMIT $s,$limit";
        $result = mysql_query($query);
    } catch (Exception $e) {
        echo 'Error connecting to the database.';
    }

    $count++;

    if ($s >= 1) { // bypass PREV link if s is 0
        $prevs = ($s - $limit);
        echo "&nbsp;<a href=\"$PHP_SELF?s=$prevs\">&lt;&lt; Prev</a>&nbsp&nbsp;";
    }

    // calculate number of pages needing links
    $pages = intval($numrows / $limit);

    if ($numrows % $limit) {
        // has a page
        $pages++;
    }

    // check to see if last page
    if (! ((($s + $limit) / $limit) == $pages) && $pages != 1) {
        // not last page so give NEXT link
        $news = $s + $limit;
        echo "&nbsp;<a href=\"$PHP_SELF?s=$news\">Next &gt;&gt;</a>";
    }

    echo '</center><table class="s"><tr><th>id</th><th>time</th><th>browser</th><th>from</th><th>to</th><th>errors</th><th>ip</th><th>srvr</th></tr>';

    while ($row = mysql_fetch_object($result)) {
        echo '<tr>';
        echo '<td>'.$row->$api_c1.'</td>';
        echo '<td>'.$row->$api_c2.'</td>';
        echo '<td>'.$row->$api_c3.'</td>';
        echo '<td>'.$row->$api_c4.'</td>';
        echo '<td>'.$row->$api_c5.'</td>';
        echo '<td>'.$row->$api_c6.'</td>';
        echo '<td><center>'.$row->$api_c7.'</center></td>';
        echo '<td><center>'.$row->$api_c8.'</center></td>';
        echo '</tr>';
    }

    mysql_close();

include '../../includes/googleAnalytics.php';
?>
</table>
</body>
</html>
