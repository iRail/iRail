<?php

require_once "IntegrationTestCase.php";

use GuzzleHttp\Client;

class VehicleIntegrationTest extends IntegrationTestCase
{

    public function test404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC");
        $this->assertEquals(404, $response->getStatusCode());

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC000");
        $this->assertEquals(404, $response->getStatusCode());

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test200()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/vehicle.php?id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
    }
}