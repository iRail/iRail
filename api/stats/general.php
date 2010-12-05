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
            #chart { width: 600px; height: 400px }
        </style>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
        <script type="text/javascript" src="jquery.gchart.min.js"></script>
        <script type="text/javascript">
            $(function () {
	$('#chart').gchart({type: 'line', maxValue: 4000,
		title: 'Calls to the iRail API', titleColor: 'red',
		backgroundColor: $.gchart.gradient('horizontal', 'ccffff', 'ccffff00'),
		series: [$.gchart.series('Hits', [
            <?
            $chartrows = array_reverse($rows, true);
            foreach($chartrows as $day => $value){
                echo "'$value',";
            }
            ?>
            ], 'red', 'ffcccc')],
		axes: [$.gchart.axis('bottom', [<?

            foreach($chartrows as $day => $value){
                echo "'" . substr($day, 0, 2). "',";
            }
            ?>
            ], 'black')],
		legend: 'left'});
            });
        </script>
    </head>
    <body>
        <div id="chart"></div>

        <?
            echo '<table width="100%" border="1"><tr><th>Date</td><th>API Requests</th></tr>';
            foreach($rows as $day => $value){
                echo '<tr><td align="right">' . $day . '</td><td>' . $value . '</td></tr>';
            }
            echo '</table>';
        ?>
    </body>
</html>
