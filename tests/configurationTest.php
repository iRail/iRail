<?php
use Dotenv\Dotenv;
class configurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDotEnvVars()
    {
        $dotenv = new Dotenv(dirname(__DIR__));
        $dotenv->load();

        // API Database credentials
        $this->assertEquals($_ENV['apiHost'], 'localhost');
        $this->assertEquals($_ENV['apiUser'], 'irail');
        $this->assertEquals($_ENV['apiTable'], 'apilog');
        $this->assertEquals($_ENV['apiPassword'], 'passwd');

        // API database Columns
        $this->assertEquals($_ENV['column1'], 'id');
        $this->assertEquals($_ENV['column2'], 'time');
        $this->assertEquals($_ENV['column3'], 'useragent');
        $this->assertEquals($_ENV['column4'], 'fromstation');
        $this->assertEquals($_ENV['column5'], 'tostation');
        $this->assertEquals($_ENV['column6'], 'errors');
        $this->assertEquals($_ENV['column7'], 'ip');
        $this->assertEquals($_ENV['column8'], 'server');

        // API nameserver
        $this->assertEquals($_ENV['apiServerName'], 0);
    }
}