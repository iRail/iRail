<?php

namespace Tests\integration;

use GuzzleHttp\Client;

class VehicleIntegrationTest extends IntegrationTestCase
{
    public function test400()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php");
        $this->assertEquals(400, $response->getStatusCode());
        
        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=");
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC000");
        $this->assertEquals(404, $response->getStatusCode());

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test200()
    {
        $client = new Client(['http_errors' => true]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
    }
}
