<?php

namespace Tests\integration;

use GuzzleHttp\Exception\GuzzleException;

class VehicleIntegrationTest extends IntegrationTestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=");
        $this->assertEquals(400, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC000");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC900");
        $this->assertEquals(404, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC538");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_ic4410WhichIsSplitFromIc4310_shouldReturnCorrectJourney()
    {
        // IC 4310 Antwerp - Mol continues to Hamont. IC 4410 is separated in Mol and continues to Heusden.
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC4310");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertNotNull($json);
        self::assertNotNull($json['vehicleinfo']);
        self::assertEquals("IC4310", $json['vehicleinfo']['shortname']);
        self::assertEquals("Antwerp-Central", $json['stops']['stop'][0]["station"]);
        self::assertTrue(in_array(
            end($json['stops']['stop'])["station"],
            ["Hamont", "Mol"]
        )); // This train is reduced to Mol at this moment

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC4410");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertEquals("IC4410", $json['vehicleinfo']['shortname'], $response->getBody());
        self::assertEquals("Mol", $json['stops']['stop'][0]["station"]);
        self::assertEquals("Heusden", end($json['stops']['stop'])["station"]);
    }

    public function test_internationalTrainIce10_shouldReturnCorrectJourney()
    {
        // IC 4310 Antwerp - Mol continues to Hamont. IC 4410 is separated in Mol and continues to Heusden.
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=ICE10");
        $this->assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertEquals("ICE10", $json['vehicleinfo']['shortname'], $response->getBody());
        self::assertEquals("BE.NMBS.008011068", $json['stops']['stop'][0]["stationinfo"]["id"]);
        self::assertEquals("Brussels-South/Brussels-Midi", end($json['stops']['stop'])["station"]);

        self::assertEquals("?", $json['stops']['stop'][0]["platform"]);
        self::assertNotEquals("?", end($json['stops']['stop'])["platform"]);
    }

    /**
     * This test checks the behaviour when the train number is too long.
     * @throws GuzzleException
     */
    public function test_idTooLong_shouldCause400BadRequest()
    {
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "vehicle.php?format=json&id=ABCDEFGHIJKLMNOPQ"
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * This test checks the behaviour when the train number isn't too long.
     * @throws GuzzleException
     */
    public function test_idNotTooLong_shouldNotCause400BadRequest()
    {
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "vehicle.php?format=json&id=ABCDEFGHIJKLMNOP"
        );
        $this->assertNotEquals(400, $response->getStatusCode());
    }

    /**
     * This test checks the behaviour when the train number is incomplete, for various incomplete train numbers.
     * @throws GuzzleException
     */
    public function test_incompleteSearchQuery_shouldCause404NotFound()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=ICE");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC 5");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC5");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC 53");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC53");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=L");
        $this->assertEquals(404, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=P");
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * This test checks the behaviour when the train number is complete, for various complete train numbers.
     * @throws GuzzleException
     */
    public function test_correctSearchQuery_shouldReturn200Ok()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=ICE10");
        $this->assertEquals(200, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC   538");
        $this->assertEquals(200, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC 538");
        $this->assertEquals(200, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=IC538");
        $this->assertEquals(200, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=S23769");
        $this->assertEquals(200, $response->getStatusCode());

        $response = self::getClient()->request("GET", self::getBaseUrl() . "vehicle.php?format=json&id=S103890");
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * This test checks the behaviour when alerts are enabled
     * @throws GuzzleException
     */
    public function test_correctSearchQueryWithAlertsEnabled_shouldReturn200Ok()
    {
        $response = self::getClient()->request(
            "GET",
            self::getBaseUrl() . "vehicle.php?format=json&id=S102063&alerts=true"
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}
