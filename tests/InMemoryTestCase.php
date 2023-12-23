<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Application;

abstract class InMemoryTestCase extends TestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        Artisan::call('migrate');
        return $app;
    }


    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        DB::update('DELETE FROM CompositionUnitUsage');
        DB::update('DELETE FROM CompositionUnit');
        DB::update('DELETE FROM CompositionHistory');
        DB::update('DELETE FROM OccupancyReports');
        DB::update('DELETE FROM RequestLog');
        parent::tearDown();
    }
}
