<html><head><title>iRail API MySQL stats</title>
        <link href="http://irail.be/css/api_stats.css" rel="stylesheet" type="text/css" />
        <link rel="apple-touch-icon" href="http://irail.be/apple-touch-icon.png" />
        <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico"/>
    </head><body>
        <?php
// "Public" API stats page
// Gives report about all days report

// include vars
        include("../../includes/dbConfig.php");

        try {
            mysql_pconnect($api_host, $api_user, $api_password);
            mysql_select_db($api_database);
            $query = "SELECT DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') day, count(id) visitors FROM $api_table group by DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') ORDER BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%Y') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%m') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d') desc";
            $result = mysql_query($query);
            echo '<table width="100%" border="1"><tr><th>Date</td><th>API Requests</th></tr>';
            while($row = mysql_fetch_object($result)) {
                echo '<tr><td align="right">' . $row->day . '</td><td>' . $row->visitors .'</td></tr>';
            }
            echo '</table>';
            mysql_close();
        } catch (Exception $e) {
            echo "Error connecting to the database.";
        }
        ?>
    </body>
</html>
