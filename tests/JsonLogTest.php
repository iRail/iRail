<?php

use Tac\Tac;
use IRail\JsonLog;

class JsonLogTest extends \PHPUnit_Framework_TestCase
{
	public function testJsonLogTail()
	{
		$log = new JsonLog(__DIR__ . '/fixtures/log_json_lines.txt');
		$jsonLog = $log->getLastEntries(5);

		$this->assertEquals(2, count($jsonLog));
		$this->assertEquals("bar1", $jsonLog[0]->foo1);
		$this->assertEquals("bar2", $jsonLog[1]->foo2);
	}

	public function testJsonLogTailSingleLine()
	{
		$log = new JsonLog(__DIR__ . '/fixtures/log_json_lines.txt');
		$jsonLog = $log->getLastEntries(1);

		$this->assertEquals(1, count($jsonLog));
		$this->assertEquals("bar2", $jsonLog[0]->foo2);
	}
}
