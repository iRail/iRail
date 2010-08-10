<?php
include "../../includes/stationlist.php";

echo "failures for ".date("d-m-y H:i"). ":<br/>";
$failures = 0;
foreach($stations as $i => $value) {
        $ch = curl_init();
        $connectionurl = "http://dev.irail.be/api/trains.php?from=harelbeke&to=" . $value;
        curl_setopt($ch, CURLOPT_URL, $connectionurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if(preg_match("/error/si", $output)){
            $failures ++;
            echo $failures . ". " . $value . "<br/>";
        }
        curl_close($ch);
}

echo "total failures: " .$failures . "\n";
?>
