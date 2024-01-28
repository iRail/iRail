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
        DB::update('DELETE FROM composition_unit_usage');
        DB::update('DELETE FROM composition_unit');
        DB::update('DELETE FROM composition_history');
        DB::update('DELETE FROM occupancy_reports');
        DB::update('DELETE FROM request_log');
        parent::tearDown();
    }
}
