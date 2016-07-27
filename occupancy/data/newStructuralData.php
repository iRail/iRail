<?php

// $argv:
// 1 = trainnumber (letter(s) + number)
// 2 = from
// 3 = to
// 4 = crowding (0=empty, 1=in between, 2=busy)
// 5 = weekday (0=weekend, 1=weekday)

if(count($argv) == 6) {
    $curl = curl_init();

    $url = "http://api.irail.be/vehicle/?id=BE.NMBS." . $argv[1];

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    curl_close($curl);

    $xml = simplexml_load_string($result);
    $stops = $xml->stops;

    $write = false;
    $errorCheck = 0;
    $text = "";
    $crowding = "?";

    foreach($stops->stop as $stop) {
        if($stop->station == $argv[2]) {
            if($errorCheck != 0) $errorCheck += 1;

            $crowding = $argv[4];
            $errorCheck += 1;
        }

        $arr = (Array)($stop->scheduledDepartureTime);
        $formattedTime = date('Hi', $arr[0]);

        if($crowding != "?") {
            $text .= $argv[1] . "," . $crowding . "," . $argv[5] . "," . $stop->station["URI"] . "," . $formattedTime . "\n";
        }

        if($stop->station == $argv[3] && $errorCheck == 1) {
            $errorCheck += 1;
            $crowding = "?";
        }
    }

    if($errorCheck != 2) echo "Error: stations niet overlopen\n";
    else $myfile = file_put_contents('structural.csv', $text.PHP_EOL, FILE_APPEND);
} else {
    echo "Error: geef 5 argumenten\n";
}

?>