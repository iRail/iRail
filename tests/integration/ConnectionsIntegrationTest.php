<?php

namespace Tests\integration;

class ConnectionsIntegrationTest extends IntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?from=008814001&to=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?to=008814001&from=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        // Gent-Dampoort - Brussel-Zuid
        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?from=008893120&to=008814001");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?from=Gent-Dampoort&to=Brussel-zuid");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?format=json");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&from=008814001");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&from=008814001&to=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&to=008814001&from=0000");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        // Gent-Dampoort - Brussel-Zuid
        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&from=008893120&to=008814001");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET",
            self::getBaseUrl() . "connections.php?format=json&from=Gent-Dampoort&to=Brussel-zuid");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }
}
