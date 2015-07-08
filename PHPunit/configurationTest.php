<?php

use Dotenv\Dotenv;

class configurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDotEnvVars()
    {
        $dotenv = new Dotenv(dirname(__DIR__));
        $dotenv->load();

        $DbColumnArray = [
            $_ENV['column1'], $_ENV['column2'], $_ENV['column3'], $_ENV['column4'],
            $_ENV['column5'], $_ENV['column6'], $_ENV['column7'], $_ENV['column8']
        ];

        $DbCredentialsArray = [
            $_ENV['apiHost'], $_ENV['apiUser'], $_ENV['apiPassword'],
            $_ENV['apiDatabase'], $_ENV['apiTable']
        ];

        $this->assertNotEmpty($DbColumnArray);
        $this->assertNotEmpty($DbCredentialsArray);
    }
}