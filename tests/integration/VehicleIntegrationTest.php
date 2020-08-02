<?php

namespace Tests\integration;

use GuzzleHttp\Client;

class VehicleIntegrationTest extends IntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
        
        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $client = new Client(['http_errors' => true]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?format=json");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?format=json&id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?format=json&id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?format=json&id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $client = new Client(['http_errors' => true]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?format=json&id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }


}
