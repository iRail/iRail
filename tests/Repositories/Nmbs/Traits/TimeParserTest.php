<?php

namespace Tests\Repositories\Nmbs\Traits;

use Carbon\Carbon;
use Irail\Exceptions\Internal\InternalProcessingException;
use Irail\Repositories\Nmbs\Traits\TimeParser;
use Tests\TestCase;

class TimeParserTest extends TestCase
{
    use TimeParser;

    public function testTransformDurationHHMMSS()
    {

        self::assertEquals(1800, $this->transformDurationHHMMSS('003000'));
        self::assertEquals(3600, $this->transformDurationHHMMSS('010000'));
        self::assertEquals(5400, $this->transformDurationHHMMSS('013000'));
        self::assertEquals(86400 + 7200 + 180 + 4, $this->transformDurationHHMMSS('01020304'));
    }

    /**
     * @throws InternalProcessingException
     */
    public function testTransformDuration()
    {
        self::assertEquals(1800, $this->transformIso8601Duration('PT1800S'));
        self::assertEquals(1800, $this->transformIso8601Duration('PT30M'));
        self::assertEquals(3600, $this->transformIso8601Duration('PT60M'));
        self::assertEquals(3600, $this->transformIso8601Duration('PT1H'));
        self::assertEquals(5400, $this->transformIso8601Duration('PT1H30M'));
        self::assertEquals(5400, $this->transformIso8601Duration('PT90M'));
        self::assertEquals(6600, $this->transformIso8601Duration('PT1H50M'));
        self::assertEquals(86400 + 7200 + 180 + 4, $this->transformIso8601Duration('P1DT2H3M4S'));
    }

    /**
     * @throws InternalProcessingException
     */
    public function testCalculateSecondsHHMMSS()
    {
        self::assertEquals(-60, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-30', '01:02:00', '2022-10-30', '01:01:00'));
        self::assertEquals(0, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-30', '01:03:00', '2022-10-30', '01:03:00'));
        self::assertEquals(124, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-30', '00:59:59', '2022-10-30', '01:02:03'));
        self::assertEquals(86400, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-01', '01:02:03', '2022-10-02', '01:02:03'));
        self::assertEquals(86400, $this->getSecondsBetweenTwoDatesAndTimes('2022-09-30', '01:02:03', '2022-10-01', '01:02:03'));
        self::assertEquals(86400 + 124, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-31', '00:59:59', '2022-11-01', '01:02:03'));
        self::assertEquals(86400, $this->getSecondsBetweenTwoDatesAndTimes('2022-10-29', '00:01:02:03', '2022-10-30', '00:01:02:03'));
    }

    /**
     * @throws InternalProcessingException
     */
    public function testTransformTime()
    {
        self::assertEquals(Carbon::createFromTimestampUTC(1667084580), $this->parseDateAndTime('2022-10-30', '01:03:00'));
        self::assertEquals(Carbon::createFromTimestampUTC(1667084580 - 86400), $this->parseDateAndTime('2022-10-29', '01:03:00'));
        self::assertEquals(Carbon::createFromTimestampUTC(1667084523), $this->parseDateAndTime('2022-10-30', '01:02:03'));
        self::assertEquals(Carbon::createFromTimestampUTC(1667084399), $this->parseDateAndTime('2022-10-30', '00:59:59'));
    }
}
