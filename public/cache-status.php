<?php
$status = opcache_get_status();

echo 'Opcache Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'] . '<br>';
echo 'Opcache Cache hits: ' . $status['opcache_statistics']['hits'] . '<br>';
echo 'Opcache Cache misses: ' . $status['opcache_statistics']['misses'] . '<br>';
echo 'Opcache Memory usage: ' . $status['memory_usage']['used_memory'] / (1024 * 1024) . ' MB' . '<br>';

if (apcu_enabled()) {
    $apcu = apcu_cache_info();
    echo 'APCU hits:' . $apcu['num_hits'] . '<br>';
    echo 'APCU misses:' . $apcu['num_misses'] . '<br>';
    echo 'APCU cache list size:' . count($apcu['cache_list']) . '<br>';
    echo '<br>';
    foreach ($apcu['cache_list'] as $item) {
        echo $item['info'] . '    hits:' . $item['num_hits'] . '<br>';
    }
} else {
    echo 'APCU not enabled';
}
