<?php

namespace Tests\Http;


use Carbon\Carbon;
use Irail\Proxy\CurlProxy;
use Tests\FakeCurlProxy;
use Tests\InteractsWithExceptionHandling;
use Tests\TestCase;

class LiveboardV2HttpIntegrationTest extends TestCase
{
    use InteractsWithExceptionHandling;

    public function test_json_missingParameters_shouldReturn404()
    {
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v2/liveboard/');
        $response->assertResponseStatus(404);

        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_invalidParameters_shouldReturn404()
    {
        $fakeProxy = new FakeCurlProxy(); // A proxy without requests defined will cause a failure on outgoing requests.
        $this->app->singleton(CurlProxy::class, fn() => $fakeProxy);

        $response = $this->get('/v2/liveboard/departure/1234');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $response = $this->get('/v2/liveboard/departure/fake');
        $response->assertResponseStatus(404);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');
    }

    public function test_json_validParameters_shouldReturn200()
    {
        $this->withoutExceptionHandling();

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

        $response = $this->get('/v2/liveboard/departure/008814001');
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

        $response = $this->get('/v2/liveboard/departure/Brussel-Zu?lang=nl');
        $response->assertResponseStatus(200);
        $this->response->assertHeader('content-type', 'application/json;charset=UTF-8');

        $this->response->assertJsonIsArray('stops');
        $this->response->assertJsonPath('station', [
            'id'            => '008814001',
            'uri'           => 'http://irail.be/stations/NMBS/008814001',
            'name'          => 'Brussel-Zuid/Bruxelles-Midi',
            'localizedName' => 'Brussel-Zuid',
            'latitude'      => 50.835707,
            'longitude'     => 4.336531,
        ]);
    }

}
