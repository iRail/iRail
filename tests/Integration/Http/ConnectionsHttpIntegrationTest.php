<?php

namespace Tests\Integration\Http;

class ConnectionsHttpIntegrationTest extends HttpIntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php");
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
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?from=008893120&to=008814001"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?from=Gent-Dampoort&to=Brussel-zuid"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
        
        $xml = simplexml_load_string($response->getBody());
        self::assertTrue(count($xml->connection) > 0, "Routeplanner should at least have one connection");
        self::assertEquals("Ghent-Dampoort", $xml->connection[0]->departure->station);
        self::assertEquals("BE.NMBS.008814001", $xml->connection[0]->arrival->station->attributes()['id']);
    }

    public function test_xml_validParameters_shouldHaveCorrectRootElement()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?from=Gent-Dampoort&to=Brussel-zuid");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
        self::assertTrue(str_starts_with($response->getBody(), "<connections "), "Root element name should be 'connections'");
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "connections.php?format=json");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&from=008814001"
        );
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&from=008814001&to=0000"
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&to=008814001&from=0000"
        );
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        // Gent-Dampoort - Brussel-Zuid
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&from=008893120&to=008814001"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&from=Gent-Dampoort&to=Brussel-zuid"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertTrue(count($json['connection']) > 0, "Routeplanner should at least have one connection");
        self::assertEquals("Ghent-Dampoort", $json['connection'][0]['departure']['station']);
        self::assertEquals("BE.NMBS.008814001", $json['connection'][0]['arrival']['stationinfo']['id']);
    }

    public function test_json_validParametersAlertsEnabled_shouldReturn200()
    {
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "connections.php?format=json&from=Gent-Dampoort&to=Brussel-zuid&alerts=true"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertTrue(count($json['connection']) > 0, "Routeplanner should at least have one connection");
        self::assertEquals("Ghent-Dampoort", $json['connection'][0]['departure']['station']);
        self::assertEquals("BE.NMBS.008814001", $json['connection'][0]['arrival']['stationinfo']['id']);
    }
}
