<?php
include_once '../vendor/autoload.php';

use IRail\JsonLog;

header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (1)));//expires in 1 second
header('Content-Type: application/json');
$log = new JsonLog('../storage/irapi.log');

print json_encode($log->getLastEntries(1000));

?>
