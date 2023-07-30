<?php

namespace Tests\Http;


use Tests\TestCase;

class LiveboardV1HttpIntegrationTest extends TestCase
{
    public function test_xml_missingParameters_shouldReturn400()
    {
        $response = $this->get('/v1/liveboard');
        $response->assertResponseStatus(400);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $response = $this->get('/v1/liveboard?id=1234');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');

        $response = $this->get('/v1/liveboard?station=fake');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
    }

    public function test_xml_validParameters_shouldHaveCorrectRootElement()
    {
        $response = $this->get('/v1/liveboard?station=Welkenraedt');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
        self::assertTrue(str_starts_with($this->response->getContent(), '<liveboard '), "Root element name should be 'liveboard'");
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $response = $this->get('/v1/liveboard?id=008844503');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');

        $response = $this->get('/v1/liveboard?station=Welkenraedt&time=1300');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');

        self::assertTrue(str_starts_with($this->response->getContent(), '<liveboard '), "Root element name should be 'liveboard'");
        $xml = simplexml_load_string($this->response->getContent());
        self::assertEquals('Welkenraedt', $xml->station);
        self::assertTrue(count($xml->departures->departure) > 0, 'Liveboard should at least have one departure');
    }

    public function test_json_missingParameters_shouldReturn400()
    {
        $response = $this->get('/v1/liveboard?format=json');
        $response->assertResponseStatus(400);

        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $response = $this->get('/v1/liveboard?format=json&id=1234');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $response = $this->get('/v1/liveboard?format=json&station=fake');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $response = $this->get('/v1/liveboard?format=json&id=008844503');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $response = $this->get('/v1/liveboard?format=json&station=Welkenraedt');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $this->response->assertJsonPath('station', 'Welkenraedt');
        $this->response->assertJsonIsArray('departures.departure');
    }

}
