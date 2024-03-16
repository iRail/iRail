<?php

namespace Irail\Proxy;

use CurlHandle;
use Illuminate\Support\Facades\Log;

class CurlProxy
{

    const CURL_HEADER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36';
    const CURL_HEADER_REFERRER = 'http://api.irail.be/';
    const CURL_TIMEOUT = 30;

    /**
     * @var CurlHttpResponse[]
     */
    private array $requests = [];

    public function get(string $url, array $parameters = [], array $headers = []): CurlHttpResponse
    {
        $url = $this->buildUrl($url, $parameters);

        Log::debug("GET $url");

        $startTime = microtime(true);

        $ch = $this->createCurlHandle($url, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $durationMillis = round(1000 * (microtime(true) - $startTime));

        Log::debug("Received response with HTTP code $httpCode for URL $url in $durationMillis ms");
        Log::debug($response);
        if ($httpCode >= 500) {
            Log::warning("HTTP Request 'GET $url' received response code $httpCode");
        }

        $responseObject = new CurlHttpResponse('GET', $url, null, $httpCode, $response, $durationMillis);

        // Keep track of which HTTP requests were made
        $this->requests[] = $responseObject;

        return $responseObject;
    }

    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @param string $url
     * @param array  $headers
     * @return CurlHandle|false
     */
    public function createCurlHandle(string $url, array $headers): false|CurlHandle
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::CURL_HEADER_USER_AGENT);
        curl_setopt($ch, CURLOPT_REFERER, self::CURL_HEADER_REFERRER);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        return $ch;
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @return string
     */
    protected function buildUrl(string $url, array $parameters): string
    {
        return $url . '?' . http_build_query($parameters, '', null,);
    }
}