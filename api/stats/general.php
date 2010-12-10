<?php
// "Public" API stats page
// Gives report about all days report
// include vars
include("../../includes/dbConfig.php");

$filter = "";
if (isset($_GET['filter'])) {
    $filter = mysql_escape_string($_GET['filter']);
}
try {
    mysql_pconnect($api_host, $api_user, $api_password);
    mysql_select_db($api_database);
    if ($filter != "") {
        $query = "SELECT DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %m %Y') day, count(id) visitors FROM $api_table WHERE $api_c3 LIKE '%$filter%' GROUP BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') ORDER BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%Y') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%m') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d') desc";
    } else {
        $query = "SELECT DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') day, count(id) visitors FROM $api_table GROUP BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') ORDER BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%Y') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%m') desc, DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d') desc";
    }
    $result = mysql_query($query);
    $rows;
    while ($row = mysql_fetch_object($result)) {
        $rows[$row->day] = $row->visitors;
    }
} catch (Exception $e) {
    echo "Error connecting to the database.";
}
//PAGE OUTPUT
?>

<html>
    <head><title>iRail API MySQL stats</title>
        <link href="http://irail.be/css/api_stats.css" rel="stylesheet" type="text/css" />
        <link rel="apple-touch-icon" href="http://irail.be/apple-touch-icon.png" />
        <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico"/>
        <style type="text/css">
            #chart { width: 900px; height: 400px }
        </style>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
        <script type="text/javascript" src="jquery.gchart.min.js"></script>
        <script type="text/javascript">
            $(function () {
	$('#chart').gchart({type: 'line', maxValue: <? echo max($rows); ?>,
		title: 'Calls to the iRail API', titleColor: 'red',
		backgroundColor: $.gchart.gradient('horizontal', 'ccffff', 'ccffff00'),
		series: [$.gchart.series('Hits', [
            <?
            $chartrows = array_reverse($rows, true);
            $count = 0;
            foreach ($chartrows as $day__ => $value) {
                if ($count != sizeof($rows) - 1 && $count != 0) {
                    echo "'$value',";
                }
                $count++;
            }
            ?>
            ], 'red', 'ffcccc')],
		axes: [$.gchart.axis('bottom', [
            <?
            $count = 0;
            foreach ($chartrows as $day__ => $value) {
                if ($count != sizeof($rows) - 1 && $count != 0) {
                    echo "'" . substr($day__, 0, 2) . "',";
                }
                $count++;
            }
            ?>
            ], 'black')],
        		legend: 'left'});
            });
        </script>
    </head>
    <body>
        <h1>Calls to the iRail API<?
            if ($filter != "") {
                echo " with $filter";
            }
            ?></h1>
        <div id="chart"></div>

        <?
            echo '<table border="1"><tr><th>Date</td><th>API Requests</th></tr>';
            $count = 0;
            foreach ($rows as $day__ => $value) {
                $date__ = strtotime($day__);
                //echo date("d m y" ,$date__) . " " . $day__. "<br/>";
                if ($count == 0) {
                    echo '<tr><td align="right"><font color="red">' . $day__ . '</font></td><td><font color="red">' . $value . '</font></td></tr>';
                } else if (date("w", $date__) == 6 || date("w", $date__) == 0) {
                    echo '<tr><td align="right"><font color="gray">' . $day__ . '</font></td><td><font color="gray">' . $value . '</font></td></tr>';
                } else {
                    echo '<tr><td align="right">' . $day__ . '</td><td>' . $value . '</td></tr>';
                }
                $count++;
            }
            echo '</table>';
        ?>
        
<?php
	include("../../includes/googleAnalytics.php");
?>

    </body>
</html>