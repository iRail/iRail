<?php

namespace Tests\integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class IntegrationTestCase extends TestCase
{

    /** @var Process */
    private static $serverProcess;

    /**
     * @var string
     */
    private static $host;

    private static $port = 8089;

    private static $isRunning = false;

    public static function tearDownAfterClass(): void
    {
        self::$serverProcess->stop();
        echo PHP_EOL . "Integration test server stopped at " . self::getBaseUrl() . PHP_EOL;
        self::$serverProcess->wait();
        self::$isRunning = false;
    }

    public static function setUpBeforeClass(): void
    {
        while (self::$isRunning) {
            sleep(1);
        }
        self::$isRunning = true;
        self::$host = "localhost:" . self::$port++;
        self::$serverProcess = new Process(["php", "-S", self::$host, "-t", dirname(dirname(__DIR__)) . "/src/api/"]);
        self::$serverProcess->start();

        usleep(500000); //wait for server to get going
        echo PHP_EOL . "Integration test server started at " . self::getBaseUrl() . PHP_EOL;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        print(self::$serverProcess->getOutput());
        self::$serverProcess->clearOutput();
        print(self::$serverProcess->getErrorOutput());
        self::$serverProcess->clearErrorOutput();
    }


    public static function getHost()
    {
        return self::$host;
    }

    public static function getBaseUrl()
    {
        return "http://" . self::$host . "/";
    }

    public static function getClient()
    {
        return new Client([
            'http_errors' => false,
            'connect_timeout' => 2,
            'timeout' => 10
        ]);
    }
}
