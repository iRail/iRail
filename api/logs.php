<?php
include_once '../vendor/autoload.php';
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (1)));//expires in 1 second
header('Content-Type: text/plain');
echo shell_exec('tail -n 1000 ../storage/irapi.log');