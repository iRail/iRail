<?php

namespace Tests\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Database\LogDao;
use Irail\Models\Dao\LogQueryType;
use Tests\InMemoryTestCase;

class LogDaoTest extends InMemoryTestCase
{
    public function testLog_multipleCalls_shouldFlushRegularly()
    {
        apcu_clear_cache(); // Clear cache for fresh start
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));
        $dao = new LogDao();
        for ($i = 0; $i < LogDao::getFlushInterval() * 2.5; $i++) {
            $dao->log(LogQueryType::LIVEBOARD, ['id' => "test-$i"], 'LogDaoTest', null);
        }
        $rowCount = DB::table('request_log')->count();
        // This test requires apc to be enabled in CLI
        self::assertEquals(2 * LogDao::getFlushInterval(), $rowCount);
    }
}
