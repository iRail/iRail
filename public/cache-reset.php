<?php

if (opcache_reset()) {
    echo 'OPcache has been reset.' . PHP_EOL;
} else {
    echo 'Failed to reset OPcache.' . PHP_EOL;
}

if (apcu_clear_cache()) {
    echo 'APCU has been reset.' . PHP_EOL;
} else {
    echo 'Failed to reset APCU.' . PHP_EOL;
}
