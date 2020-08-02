<?php

namespace Tests\integration;

use GuzzleHttp\Client;

class ConnectionsIntegrationTest extends IntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/connections.php");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $client = new Client(['http_errors' => false]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?from=008814001&to=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?to=008814001&from=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $client = new Client(['http_errors' => false]);

        // Gent-Dampoort - Brussel-Zuid
        $response = $client->request("GET", "http://localhost:8080/connections.php?from=008893120&to=008814001");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?from=Gent-Dampoort&to=Brussel-zuid");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $client = new Client(['http_errors' => false]);
        
        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $client = new Client(['http_errors' => false]);
        
        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&from=008814001&to=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&to=008814001&from=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $client = new Client(['http_errors' => false]);

        // Gent-Dampoort - Brussel-Zuid
        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&from=008893120&to=008814001");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = $client->request("GET", "http://localhost:8080/connections.php?format=json&from=Gent-Dampoort&to=Brussel-zuid");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }
}
