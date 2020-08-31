<?php


namespace Tests\integration;


class StationsIntegrationTest extends IntegrationTestCase
{
    public function test_xml_noParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "stations.php");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/xml;charset=UTF-8", $response->getHeader("content-type")[0]);
    }

    public function test_json_noParameters_shouldReturn200()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "stations.php?format=json");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);
        self::assertTrue(count($json["station"]) > 670, "There should be at least 670 stations in the stations list");

        $brusselsCentralFound = false;
        foreach ($json["station"] as $station) {
            if ($station["id"] == "BE.NMBS.008813003") {
                $brusselsCentralFound = true;
                self::assertEquals("Brussels-Central", $station["name"]);
            }
        }
        self::assertTrue($brusselsCentralFound);
    }

    function test_json_langNl_shouldTranslateCorrectly()
    {
        $response = self::getClient()->request("GET", self::getBaseUrl() . "stations.php?format=json&lang=nl");
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals("application/json;charset=UTF-8", $response->getHeader("content-type")[0]);

        $json = json_decode($response->getBody(), true);

        $brusselsCentralFound = false;
        foreach ($json["station"] as $station) {
            if ($station["id"] == "BE.NMBS.008813003") {
                $brusselsCentralFound = true;
                self::assertEquals("Brussel-Centraal", $station["name"]);
            }
        }
        self::assertTrue($brusselsCentralFound);
    }
}