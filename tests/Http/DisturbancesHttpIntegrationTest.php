<?php


namespace Tests\Http;

use function Tests\Integration\Http\str_starts_with;

class DisturbancesHttpIntegrationTest extends HttpIntegrationTestCase
{
    public function test_xml_noParameters_shouldReturn200()
    {
        $response = self::getClient()->request('GET', self::getBaseUrl() . 'disturbances.php');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/xml;charset=UTF-8', $response->getHeader('content-type')[0]);
        self::assertTrue(str_starts_with($response->getBody(), '<disturbances '), "Root element name should be 'disturbances'");
        $xml = simplexml_load_string($response->getBody());
        self::assertTrue(count($xml->disturbance) > 0, 'There should be at least one disturbance');
    }


    public function test_xml_validParameters_shouldHaveCorrectRootElement()
    {
        $response = self::getClient()->request('GET', self::getBaseUrl() . 'disturbances.php');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/xml;charset=UTF-8', $response->getHeader('content-type')[0]);
        self::assertTrue(str_starts_with($response->getBody(), '<disturbances '), "Root element name should be 'disturbances'");
    }


    public function test_json_noParameters_shouldReturn200()
    {
        $response = self::getClient()->request('GET', self::getBaseUrl() . 'disturbances.php?format=json');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json;charset=UTF-8', $response->getHeader('content-type')[0]);

        $json = json_decode($response->getBody(), true);
        self::assertTrue(count($json['disturbance']) > 0, 'There should be at least one disturbance');
        self::assertTrue(key_exists('title', $json['disturbance'][0]));
        self::assertTrue(key_exists('description', $json['disturbance'][0]));
        self::assertTrue(key_exists('timestamp', $json['disturbance'][0]));
    }
}
