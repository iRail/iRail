<?php

namespace Tests\integration;

use GuzzleHttp\Client;

class LiveboardIntegrationTest extends IntegrationTestCase
{

    public function test400()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/liveboard.php");
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/liveboard.php?id=1234");
        $this->assertEquals(404, $response->getStatusCode());

        $response = $client->request("GET", "http://localhost:8080/liveboard.php?station=fake");
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test200()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/liveboard.php?id=008844503");
        $this->assertEquals(200, $response->getStatusCode());

        $response = $client->request("GET", "http://localhost:8080/liveboard.php?station=Welkenraedt");
        $this->assertEquals(200, $response->getStatusCode());
    }
}