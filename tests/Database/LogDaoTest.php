<?php

namespace Tests\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Irail\Database\LogDao;
use Irail\Models\Dao\LogQueryType;
use Tests\InMemoryTestCase;

class LogDaoTest extends InMemoryTestCase
{
    function testLog_multipleCalls_shouldFlushRegularly()
    {
        Carbon::setTestNow(Carbon::createFromDate(2023, 12, 21));
        $dao = new LogDao();
        for ($i = 0; $i < LogDao::DB_FLUSH_SIZE * 2.5; $i++) {
            $dao->log(LogQueryType::LIVEBOARD, ['id' => "test-$i"], 'LogDaoTest', null);
        }
        $rowCount = DB::table('request_log')->count();
        self::assertEquals(2 * LogDao::DB_FLUSH_SIZE, $rowCount);
    }
}