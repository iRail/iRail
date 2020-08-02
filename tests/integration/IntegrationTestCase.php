<?php
namespace Tests\integration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class IntegrationTestCase extends TestCase
{

    /** @var Process */
    private static $serverProcess;

    public static function tearDownAfterClass(): void
    {
        print(self::$serverProcess->getErrorOutput());
        self::$serverProcess->stop();
    }

    public static function setUpBeforeClass(): void
    {
        self::$serverProcess = new Process("php -S localhost:8080 -t " . dirname(dirname(__DIR__)) . "/src/api/");
        self::$serverProcess->start();

        usleep(100000); //wait for server to get going
    }
}