<?php
// "Public" API stats page
// Gives report about all days report
// include vars
use Dotenv\Dotenv;

$dotenv = new Dotenv(dirname(__DIR__));
$dotenv->load();

$filter = "";
if (isset($_GET['filter'])) {
    $filter = mysql_escape_string($_GET['filter']);
}
try {
    mysql_pconnect($_ENV['apiHost'], $_ENV['apiUser'], $_ENV['apiPassword']);
    mysql_select_db($api_database);

    $api_c2 = $_ENV['column2'];
    $api_table = $_ENV['apiTable'];

    if ($filter != "") {
        $query = "
          SELECT DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %m %Y') day, count(id) visitors
          FROM $api_table
          WHERE $api_c3 LIKE '%$filter%'
          GROUP BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y')
          ORDER BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%Y') desc,
            DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%m') desc,
            DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d') desc LIMIT 1000";
    } else {
        $query = "
          SELECT DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y') day, count(id) visitors
          FROM $api_table
          GROUP BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d %b %Y')
          RDER BY DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%Y') desc,
            DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%m') desc,
            DATE_FORMAT(STR_TO_DATE($api_c2,'%a, %d %b %Y %T'), '%d') desc LIMIT 1000";
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
        <link href="./api_stats.css" rel="stylesheet" type="text/css" />
        <link rel="apple-touch-icon" href="http://irail.be/apple-touch-icon.png" />
        <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico"/>
        <style type="text/css">
            #chart { width: 900px; height: 400px }
        </style>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
        <script type="text/javascript" src="jquery.gchart.min.js"></script>
   <script language="javascript" src="http://www.google.com/jsapi"></script>
    </head>
    <body>
        <h1>Uncached iRail API calls<?
            if ($filter != "") {
                echo " with $filter";
            }
            ?></h1>
        <div id="chart"></div>

   <script type="text/javascript">
      var queryString = '';
      var dataUrl = '';

      function onLoadCallback() {
        if (dataUrl.length > 0) {
          var query = new google.visualization.Query(dataUrl);
          query.setQuery(queryString);
          query.send(handleQueryResponse);
        } else {
          var dataTable = new google.visualization.DataTable();
          dataTable.addRows(<? echo sizeof($rows); ?>);
          dataTable.addColumn('number');
            <?
            $chartrows = array_reverse($rows, true);
            $count = 0;
            foreach ($chartrows as $day__ => $value) {
                if ($count != sizeof($rows) - 1 && $count != 0) {
                    echo "dataTable.setValue($count, 0,$value);";
                }
                $count++;
            }
            ?>
          draw(dataTable);
        }
      }

      function draw(dataTable) {
        var vis = new google.visualization.ImageChart(document.getElementById('chart'));
        var options = {
          chxl: '',
          chxp: '',
          chxr: '0,0,<? echo max($rows); ?>',
          chxs: '',
          chxtc: '',
          chxt: 'y',
          chbh: 'a,0,0',
          chs: '800x365',
          cht: 'bvg',
          chco: 'A2C180',
          chd: 's:GflxYlS',
          chdl: '',
          chg: '-1,0,0,4',
          chtt: 'Uncached+iRail+API+calls'
        };
        vis.draw(dataTable, options);
      }

      function handleQueryResponse(response) {
        if (response.isError()) {
          alert('Error in query: ' + response.getMessage() + ' ' + response.getDetailedMessage());
          return;
        }
        draw(response.getDataTable());
      }

      google.load("visualization", "1", {packages:["imagechart"]});
      google.setOnLoadCallback(onLoadCallback);

    </script>


    <style type="text/css">
        td {
            text-align: right;
        }

        .red {
            color: red;
        }

        .gray {
            color: gray;
        }
    </style>


<p>As of <a href="http://project.irail.be/ticket/85" target="_blank">15/03/2011</a> this graph only displays uncached API calls. This displays the amount of requests we send to our data providers (such as NMBS/SNCB).</p>

        <?
            echo '<table border="1"><tr><th>Date</td><th>API Requests</th></tr>';
            $count = 0;
            foreach ($rows as $day__ => $value) {
                $date__ = strtotime($day__);
                //echo date("d m y" ,$date__) . " " . $day__. "<br/>";
                if ($count == 0) {
                    echo '<tr><td><span class="red">' . $day__ . '</span></td><td><span class="red">' . $value . '</span></td></tr>';
                } else if (date("w", $date__) == 6 || date("w", $date__) == 0) {
                    echo '<tr><td><span class="gray">' . $day__ . '</span></td><td><span class="gray">' . $value . '</span></td></tr>';
                } else {
                    echo '<tr><td>' . $day__ . '</td><td>' . $value . '</td></tr>';
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
