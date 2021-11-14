<?php

namespace Tests\Unit\Log;

use Irail\log\JsonLog;
use PHPUnit\Framework\TestCase;

class JsonLogTest extends TestCase
{
    public function testJsonLogTail()
    {
        $log = new JsonLog(__DIR__ . '/log_json_lines.txt');
        $jsonLog = $log->getLastEntries(5);

        $this->assertEquals(2, count($jsonLog));
        $this->assertEquals("bar1", $jsonLog[0]->foo1);
        $this->assertEquals("bar2", $jsonLog[1]->foo2);
    }

    public function testJsonLogTailSingleLine()
    {
        $log = new JsonLog(__DIR__ . '/log_json_lines.txt');
        $jsonLog = $log->getLastEntries(1);

        $this->assertEquals(1, count($jsonLog));
        $this->assertEquals("bar2", $jsonLog[0]->foo2);
    }
}
