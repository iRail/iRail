<?php
include "../../includes/stationlist.php";
/*
//	for safety reasons, commented
echo "failures for ".date("d-m-y H:i"). ":<br/>";
$failures = 0;
foreach($stations as $i => $value) {
    if($value == "HARELBEKE") {
        continue;
    }
    $ch = curl_init();
    $station2 = urlencode($value);
    $connectionurl = "http://dev.irail.be/api/trains.php?from=harelbeke&to=" . $station2;
    curl_setopt($ch, CURLOPT_URL, $connectionurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    if(preg_match("/error/si", $output)) {
        $failures ++;
        echo $failures . ". " . $value . "<br/>";
    }
    curl_close($ch);
}

echo "total failures: " .$failures . "\n";
*/
?>
