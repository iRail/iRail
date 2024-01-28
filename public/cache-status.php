<?php
$status = opcache_get_status();

echo 'Opcache Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'] . PHP_EOL;
echo 'Opcache Cache hits: ' . $status['opcache_statistics']['hits'] . PHP_EOL;
echo 'Opcache Cache misses: ' . $status['opcache_statistics']['misses'] . PHP_EOL;
echo 'Opcache Memory usage: ' . $status['memory_usage']['used_memory'] / (1024 * 1024) . ' MB' . PHP_EOL;

if (apcu_enabled()) {
    $apcu = apcu_cache_info();
    echo 'APCU hits:' . $apcu['num_hits'] . PHP_EOL;
    echo 'APCU misses:' . $apcu['num_misses'] . PHP_EOL;
    echo 'APCU cache list size:' . count($apcu['cache_list']) . PHP_EOL;
} else {
    echo 'APCU not enabled';
}
