<?php

namespace Tests\integration;

class VehicleIntegrationTest extends IntegrationTestCase
{
    protected function setUp() : void
    {
        // This endpoint needs to be fixed before we can enable the tests in CI.
        $this->markTestIncomplete();
    }

    public function test_xml_missingParameters_shouldReturn400()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
        
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $response =self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }
}
