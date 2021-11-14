<?php

namespace Tests\Integration\Http;

use GuzzleHttp\Exception\GuzzleException;

class LiveboardHttpIntegrationTest extends HttpIntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?id=1234");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?station=fake");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldHaveCorrectRootElement()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?station=Welkenraedt");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
        self::assertTrue(str_starts_with($response->getBody(), "<liveboard "), "Root element name should be 'liveboard'");
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?id=008844503");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?station=Welkenraedt&time=1300");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        self::assertTrue(str_starts_with($response->getBody(), "<liveboard "), "Root element name should be 'liveboard'");
        $xml = simplexml_load_string($response->getBody());
        self::assertEquals("Welkenraedt", $xml->station);
        self::assertTrue(count($xml->departures->departure) > 0, "Liveboard should at least have one departure");
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?format=json");
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?format=json&id=1234");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?format=json&station=fake");
        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?format=json&id=008844503");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "liveboard.php?format=json&station=Welkenraedt"
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertEquals("Welkenraedt", $json['station']);
        self::assertTrue(count($json['departures']['departure']) > 0, "Liveboard should at least have one departure");
    }

    /**
     * This test checks the behaviour when alerts are enabled
     * @throws GuzzleException
     */
    public function test_correctSearchQueryWithAlertsEnabled_shouldReturn200Ok()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "liveboard.php?format=json&id=008844503&alerts=true");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }
}
