<?php

namespace Tests\Http;


use Carbon\Carbon;
use Irail\Proxy\CurlProxy;
use Tests\FakeCurlProxy;
use Tests\InteractsWithExceptionHandling;
use Tests\TestCase;

class LiveboardV1HttpIntegrationTest extends TestCase
{
    use InteractsWithExceptionHandling;

    public function test_xml_missingParameters_shouldReturn400()
    {
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v1/liveboard');
        $response->assertResponseStatus(400);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
    }

    public function test_xml_invalidParameters_shouldReturn404()
    {
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v1/liveboard?id=1234');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');

        $response = $this->get('/v1/liveboard?station=fake');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
    }

    public function test_xml_validParameters_shouldHaveCorrectRootElement()
    {
        $this->withoutExceptionHandling(); // Don't catch errors (causing HTTP errors), but crash directly in the test

        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);
        $fakeProxy->fakeGet('https://mobile-riv.api.belgianrail.be/api/v1.0/dacs',
            [
                'query'    => 'DeparturesApp',
                'UicCode'  => 8814001,
                'FromDate' => '2023-07-28 11:30:00',
                'Count'    => 100
            ],
            ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl'],
            200, __DIR__ . '/../Fixtures/departures-brussels-20230728.json');
        Carbon::setTestNow(Carbon::parse('2023-07-28 11:30:00+0200'));

        $response = $this->get('/v1/liveboard?station=Brussel-zuid');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/xml;charset=UTF-8');
        self::assertTrue(str_starts_with($this->response->getContent(), '<liveboard '), "Root element name should be 'liveboard'");
    }

    public function test_xml_validParameters_shouldReturn200()
    {
        $this->withoutExceptionHandling(); // Don't catch errors (causing HTTP errors), but crash directly in the test

        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);
        $fakeProxy->fakeGet('https://mobile-riv.api.belgianrail.be/api/v1.0/dacs',
            [
                'query'    => 'DeparturesApp',
                'UicCode'  => 8844503,
                'FromDate' => '2023-07-28 11:30:00',
                'Count'    => 100
            ],
            ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl'],
            200, __DIR__ . '/../Fixtures/departures-brussels-20230728.json');
        Carbon::setTestNow(Carbon::parse('2023-07-28 11:30:00+0200'));

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
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v1/liveboard?format=json');
        $response->assertResponseStatus(400);

        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v1/liveboard?format=json&id=1234');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $response = $this->get('/v1/liveboard?format=json&station=fake');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $this->withoutExceptionHandling(); // Don't catch errors (causing HTTP errors), but crash directly in the test

        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);
        $fakeProxy->fakeGet('https://mobile-riv.api.belgianrail.be/api/v1.0/dacs',
            [
                'query'    => 'DeparturesApp',
                'UicCode'  => 8814001,
                'FromDate' => '2023-07-28 11:30:00',
                'Count'    => 100
            ],
            ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl'],
            200, __DIR__ . '/../Fixtures/departures-brussels-20230728.json');
        Carbon::setTestNow(Carbon::parse('2023-07-28 11:30:00+0200'));

        $response = $this->get('/v1/liveboard?format=json&id=008814001');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');


        $fakeProxy->fakeGet('https://mobile-riv.api.belgianrail.be/api/v1.0/dacs',
            [
                'query'    => 'DeparturesApp',
                'UicCode'  => 8814001,
                'FromDate' => '2023-07-28 11:30:00',
                'Count'    => 100,
                'lang'     => 'nl'
            ],
            ['x-api-key: IOS-v0001-20190214-YKNDlEPxDqynCovC2ciUOYl8L6aMwU4WuhKaNtxl'],
            200, __DIR__ . '/../Fixtures/departures-brussels-20230728.json');

        $response = $this->get('/v1/liveboard?format=json&station=Brussel-Zu&lang=nl');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $this->response->assertJsonPath('station', 'Brussel-Zuid');
        $this->response->assertJsonPath('stationinfo.name', 'Brussel-Zuid');
        $this->response->assertJsonPath('stationinfo.standardname', 'Brussel-Zuid/Bruxelles-Midi');
        $this->response->assertJsonIsArray('departures.departure');
    }

}
