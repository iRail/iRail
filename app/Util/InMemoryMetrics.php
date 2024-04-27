<?php

namespace Irail\Util;

use Carbon\Carbon;

class InMemoryMetrics
{
    public static function countError(): void
    {
        self::increaseMetric('metrics|error');
    }

    public static function getErrorCountsLastHour(): array
    {
        $metric = 'metrics|error';
        return self::getMetricCountsPerMinuteForHour($metric);
    }

    public static function countRivCall(): void
    {
        self::increaseMetric('metrics|rivCall');
    }

    public static function getRivCallCountsLastHour(): array
    {
        $metric = 'metrics|rivCall';
        return self::getMetricCountsPerMinuteForHour($metric);
    }

    public static function countRateLimitRejection(): void
    {
        self::increaseMetric('metrics|rateLimit');
    }

    public static function getRateLimitRejectionCountsLastHour(): array
    {
        $metric = 'metrics|rateLimit';
        return self::getMetricCountsPerMinuteForHour($metric);
    }

    /**
     * @return void
     */
    private static function increaseMetric(string $metric): void
    {
        $minuteTimestamp = Carbon::now()->format('Y-m-d H:i');
        $hourTimestamp = substr($minuteTimestamp, 0, 13);
        apcu_add($metric . '|' . $minuteTimestamp, 0, 3600); // 1 hour
        apcu_inc($metric . '|' . $minuteTimestamp);
        apcu_add($metric . '|' . $hourTimestamp, 0, 10800); // 3 hours
        apcu_inc($metric . '|' . $minuteTimestamp);
    }

    /**
     * @param string $metric
     * @return array
     */
    private static function getMetricCountsPerMinuteForHour(string $metric): array
    {
        $date = Carbon::now();
        $result = [];
        for ($i = 1; $i <= 60; $i++) {
            $date = $date->subMinute();
            $formattedDate = $date->format('Y-m-d H:i');
            $count = apcu_fetch($metric . '|' . $formattedDate);
            $result[$formattedDate] = $count;
        }
        return $result;
    }
}