<?php

namespace Irail\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Irail\Database\HistoricCompositionDao;
use Irail\Database\LogDao;
use Irail\Database\OccupancyDao;
use Irail\Models\Dao\LogQueryType;
use Irail\Repositories\Gtfs\GtfsRepository;
use Irail\Repositories\Gtfs\GtfsTripStartEndExtractor;
use Irail\Util\InMemoryMetrics;

class StatusController extends BaseIrailController
{
    private LogDao $logRepository;
    private GtfsRepository $gtfsRepository;
    private GtfsTripStartEndExtractor $tripStartEndExtractor;

    public function __construct(LogDao $logRepository, GtfsRepository $gtfsRepository, GtfsTripStartEndExtractor $tripStartEndExtractor)
    {
        $this->logRepository = $logRepository;
        $this->gtfsRepository = $gtfsRepository;
        $this->tripStartEndExtractor = $tripStartEndExtractor;
    }

    public function showStatus(Request $request): string
    {
        $apcStatus = $this->getApcStatus();
        $opcacheStatus = $this->getOpcacheStatus();
        $gtfsStatus = $this->getGtfsStatus();
        $requestStatus = $this->getRequestsStatus();
        $rivRequestStatus = $this->getRivRateLimitStatus();
        $errorRates = $this->getErrorRateStatus();
        $rateLimitRates = $this->getRateLimitingStatus();
        $memoryStatus = $this->getMemoryStatus();
        return 'Mem: ' . $memoryStatus . '<br>'
            . 'GTFS: ' . $gtfsStatus . '<br>'
            . 'APC: ' . $apcStatus . '<br>'
            . 'NMBS RIV: ' . $rivRequestStatus . '<br>'
            . 'Errors: ' . $errorRates . '<br>'
            . 'Rate Limiting: ' . $rateLimitRates . '<br>'
            . 'Logs: ' . $requestStatus . '<br>'
            . 'Opcache: ' . $opcacheStatus;
    }

    public function warmupCache(): string
    {
        // Step 1: preload occupancy data, which is only performed once
        /**
         * @var OccupancyDao $occupancyDao
         */
        $occupancyDao = app(OccupancyDao::class);
        $occupancyDao->readLevelsForDateIntoCache(Carbon::now());

        // Step 2: preload composition data, which is only performed once
        /**
         * @var HistoricCompositionDao $compositionDao
         */
        $compositionDao = app(HistoricCompositionDao::class);
        $compositionDao->warmupCache();

        // Step 3: preload GTFS data, which is performed if the cache has expired
        Log::info('Warming up GTFS Cache');
        if ($this->gtfsRepository->getCachedTrips()) {
            Log::info('GTFS cache is already loaded, not warming up');
            return 'OK: Already loaded';
        }
        // Calling this method will load the cache
        $trips = $this->gtfsRepository->getTripsByJourneyNumberAndStartDate();
        $tripsToday = $this->tripStartEndExtractor->getTripsWithStartAndEndByDate(Carbon::now());
        Log::info('Warmed up GTFS Cache');
        $gtfsResult = 'OK: Loaded ' . count($trips) . ' journeys, ' . count($tripsToday) . ' today<br>';

        return $gtfsResult . $this->getMemoryStatus();
    }

    public function resetCache(): string
    {
        Log::warning('Clearing cache!');
        $result = '';
        if (opcache_reset()) {
            $result .= 'OPcache has been reset.<br>';
        } else {
            $result .= 'Failed to reset OPcache.<br>';
        }

        if (apcu_clear_cache()) {
            $result .= 'APCU has been reset.<br>';
        } else {
            $result .= 'Failed to reset APCU.<br>';
        }
        return $result;
    }

    private function getMemoryStatus(): string
    {
        $bytes_megabytes_factor = 1024 * 1024;
        $currentUsage = round(memory_get_usage() / $bytes_megabytes_factor, 2);
        $peakUsage = round(memory_get_peak_usage() / $bytes_megabytes_factor, 2);

        $result = '<br>';

        $fh = fopen('/proc/meminfo', 'r');
        $linesRead = 0;
        while ($linesRead++ < 8 && $line = fgets($fh)) {
            $line = str_replace(' ', '', $line);
            $parts = explode(':', $line);
            $usageKb = intval(substr($parts[1], 0, -2));
            $result .= $parts[0] . ":\t" . round($usageKb / 1024) . 'Mb<br>';
        }
        fclose($fh);

        $result .= 'Request memory usage ' . $currentUsage . 'MB, peak ' . $peakUsage . 'MB<br>';
        return $result;
    }

    /**
     * @return string
     */
    public function getGtfsStatus(): string
    {
        $cachedGtfsData = $this->gtfsRepository->getCachedTrips();
        $gtfsStatus = 'GTFS configured to read '
            . GtfsRepository::getGtfsDaysBackwards() . ' day(s) back, '
            . GtfsRepository::getGtfsDaysForwards() . ' day(s) forward.<br>';
        $gtfsStatus .= $cachedGtfsData === false
            ? 'Not loaded'
            : 'Loaded ' . count($cachedGtfsData->getValue()) . " vehicle journeys since {$cachedGtfsData->getCreatedAt()}, valid until {$cachedGtfsData->getExpiresAt()}";
        return $gtfsStatus . '<br>';
    }

    private function getApcStatus(): string
    {
        if (apcu_enabled()) {
            $apcu = apcu_cache_info();
            $result = 'APCU hits:' . $apcu['num_hits'] . '<br>';
            $result .= 'APCU misses:' . $apcu['num_misses'] . '<br>';
            $result .= 'APCU cache list size:' . count($apcu['cache_list']) . '<br>';
            /*foreach ($apcu['cache_list'] as $item) {
                $result .= $item['info'] . '    hits:' . $item['num_hits'] . '<br>';
            }*/
            return $result;
        } else {
            return 'APCU not enabled';
        }
    }

    private function getOpcacheStatus(): string
    {
        $status = opcache_get_status();

        $result = 'Opcache Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'] . '<br>';
        $result .= 'Opcache Cache hits: ' . $status['opcache_statistics']['hits'] . '<br>';
        $result .= 'Opcache Cache misses: ' . $status['opcache_statistics']['misses'] . '<br>';
        $result .= 'Opcache Memory usage: ' . round($status['memory_usage']['used_memory'] / (1024 * 1024)) . ' MB' . '<br>';
        return $result;
    }

    private function getRequestsStatus(): string
    {
        $averageMinuteSpan = 5;
        $logEntries = $this->logRepository->readLogsPastMinutes($averageMinuteSpan);
        $requestPerMinute = [];

        $result = "Request rates averaged over $averageMinuteSpan minutes:<br>";
        $result .= 'Total: ' . count($logEntries) . ' requests in ' . $averageMinuteSpan . ' minutes<br>';

        foreach (LogQueryType::cases() as $logQueryType) {
            $requestPerMinute[$logQueryType->value] = 0;
        }
        foreach ($logEntries as $logEntry) {
            $requestPerMinute[$logEntry->getQueryType()->value] += 1;
        }

        foreach ($requestPerMinute as $type => $requestCount) {
            $result .= "$type: " . round($requestCount / $averageMinuteSpan, 1) . ' requests per minute<br>';
        }
        return $result;
    }

    private function getRivRateLimitStatus(): string
    {
        $result = 'Request rate towards NMBS RIV: <br>';
        $metric = InMemoryMetrics::getRivCallCountsLastHour();
        $result .= $this->formatMetric($metric, 5);
        return $result;
    }

    private function getErrorRateStatus(): string
    {
        $result = 'Error counts: <br>';
        $metric = InMemoryMetrics::getErrorCountsLastHour();
        $result .= $this->formatMetric($metric, 15);
        return $result;
    }

    private function getRateLimitingStatus(): string
    {
        $result = 'Rate limit block counts: <br>';
        $metric = InMemoryMetrics::getRateLimitRejectionCountsLastHour();
        $result .= $this->formatMetric($metric, 15);
        return $result;
    }

    /**
     * @param array  $metric
     * @param string $result
     * @return string
     */
    public function formatMetric(array $metric, int $valuesToPrint): string
    {
        $result = '';
        $timestamps = array_keys($metric);
        $counts = array_values($metric);
        for ($i = 0; $i < $valuesToPrint; $i++) {
            $result .= $timestamps[$i] . ': ' . ($counts[$i] !== false ? $counts[$i] : '0') . '<br>';
        }
        return $result;
    }
}
