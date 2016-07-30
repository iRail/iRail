<?php

include_once '../../vendor/autoload.php';
use MongoDB\Collection as Collection;

$dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
$dotenv->load();
$mongodb_url = getenv('MONGODB_URL');
$mongodb_db = getenv('MONGODB_DB');

$m = new MongoDB\Driver\Manager($mongodb_url);
$structural = new Collection($m, $mongodb_db, 'structural');
$occupancy = new MongoDB\Collection($m, $mongodb_db, 'occupancy');

date_default_timezone_set('Europe/Brussels');
$dayOfTheWeek = date('N');

$weekendCheck = 6;

for ($i=0; $i<2; $i++) {
    $isWeekday = 1;

    if ($dayOfTheWeek == $weekendCheck-$i || $dayOfTheWeek == $weekendCheck-$i+1) {
        $isWeekday = 0;
    }

    $structuralData = $structural->find(array('weekday' => $isWeekday));

    foreach ($structuralData as $structuralElement) {
        $extra = $i;

        $date = date('Ymd', strtotime(date('Y-m-d') . ' + ' . $extra . ' days'));
        $connection = 'http://irail.be/connections/'.substr(basename($structuralElement->from), 2)."/".$date."/".$structuralElement->vehicle;

        $structuralToOccupancy = array(
            'connection' => $connection,
            'vehicle' => 'http://irail.be/vehicle/' . $structuralElement->vehicle,
            'from' => $structuralElement->from,
            'date' => $date,
            'structural' => $structuralElement->occupancy,
            'occupancy' => $structuralElement->occupancy
        );

        $occupancy->insertOne($structuralToOccupancy);
    }
}
