<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Testing\Assert;
use Irail\Proxy\CurlHttpResponse;
use Irail\Proxy\CurlProxy;

class FakeCurlProxy extends CurlProxy
{
    private array $definedResults;
    private array $expectedHeaders;

    public function get(string $url, array $parameters = [], array $headers = []): CurlHttpResponse
    {
        $url = $this->buildUrl($url, $parameters);
        if (!key_exists($url, $this->definedResults)) {
            Assert::fail('No response defined for request to URL ' . $url);
        }
        Assert::assertEquals($this->expectedHeaders[$url], $headers, 'Headers for GET request to ' . $url . ' do not match expectations');
        return $this->definedResults[$url];
    }

    /**
     * Define a response which should be returned when an HTTP call is made
     * @param string      $url The URL which will be requested
     * @param array       $parameters The URL parameters which will be included in the request
     * @param array       $headers The headers which are expected for the request
     * @param int         $responseCode The response code to return
     * @param string|null $responseFixtureFile The file containing the response data
     * @return void
     */
    public function fakeGet(string $url, array $parameters = [], array $headers = [], int $responseCode, ?string $responseFixtureFile): void
    {
        $url = $this->buildUrl($url, $parameters);
        $responseBody = $responseFixtureFile ? file_get_contents($responseFixtureFile) : null;
        $this->definedResults[$url] = new CurlHttpResponse(
            Carbon::now(),
            'GET',
            $url,
            null,
            $responseCode,
            $responseBody,
            1
        );
        $this->expectedHeaders[$url] = $headers;
    }
}