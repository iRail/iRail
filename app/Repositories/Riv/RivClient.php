<?php

namespace Irail\Repositories\Riv;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Irail\Exceptions\NoResultsException;
use Irail\Exceptions\Upstream\UpstreamParameterException;
use Irail\Exceptions\Upstream\UpstreamRateLimitException;
use Irail\Exceptions\Upstream\UpstreamServerException;
use Irail\Exceptions\Upstream\UpstreamServerTimeoutException;
use Irail\Exceptions\Upstream\UpstreamServerUnavailableException;
use Irail\Models\CachedData;
use Irail\Proxy\CurlProxy;
use Irail\Traits\Cache;
use Irail\Util\InMemoryMetrics;

class RivClient
{
    use Cache;

    # The maximum number of outgoing requests per minute towards NMBS
    private int $rateLimit;
    private CurlProxy $curlProxy;

    public function __construct(CurlProxy $curlProxy)
    {
        $this->setCachePrefix('RivClient');
        $this->curlProxy = $curlProxy;
        $this->rateLimit = env('NMBS_RIV_RATE_LIMIT_PER_MINUTE', 10);
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @return CachedData<array> The downloaded json data
     */
    public function makeApiCallToMobileRivApi(string $url, array $parameters, int $ttl = 15): CachedData
    {
        $cacheKey = str_replace('/', '_', $url) . '?' . http_build_query($parameters);
        $cachedResponse = $this->getCacheOrUpdate($cacheKey, function () use ($url, $parameters) {
            return $this->fetchRateLimitedRivResponse($url, $parameters);
        }, $ttl);
        $stringData = $cachedResponse->getValue();
        $jsonData = $this->validateAndDecodeRivResponse($stringData);
        // Replace the string data with the json data in the CachedData object.
        $cachedResponse->setValue($jsonData);
        return $cachedResponse;
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @return mixed
     */
    private function fetchRateLimitedRivResponse(string $url, array $parameters)
    {
        $response = RateLimiter::attempt(
            'riv-request',
            $this->rateLimit,
            function () use ($url, $parameters) {
                InMemoryMetrics::countRivCall();
                return $this->curlProxy->get($url, $parameters, ['x-api-key: ' . getenv('NMBS_RIV_API_KEY')]);
            },
            60 // 60 seconds buckets, i.e. rate limiting per minute
        );
        if ($response === false) {
            $currentRequestRate = $this->rateLimit - RateLimiter::remaining('riv-request', $this->rateLimit);
            $message = "Current request rate towards NMBS is $currentRequestRate requests per minute. "
                . "Outgoing request blocked due to rate limiting configured at {$this->rateLimit} requests per minute";
            Log::error($message);
            throw new UpstreamRateLimitException($message);
        }
        return $response->getResponseBody();
    }

    /**
     * @param string $response The response data from the RIV API.
     * @return array JSON data as an associative array
     * @throws UpstreamServerException
     */
    public static function validateAndDecodeRivResponse(string $response): array
    {
        if (empty($response)) {
            throw new UpstreamServerException('The server did not return any data.', 500);
        }
        $json = json_decode($response, true);
        if ($json == null && str_contains($response, 'ERROR reason : error : 9000 :')) {
            throw new UpstreamServerUnavailableException('iRail could not read data from the remote server.');
        } elseif ($json == null && str_contains($response, ': error :')) {
               throw new UpstreamServerException('The remote server returned an error: ' . $response, 504);
        } elseif ($json == null || !is_array($json)) {
            Log::error('Failed to read raw json data:');
            Log::error($response);
            throw new UpstreamServerException('iRail could not read data from the remote server.');
        }
        self::throwExceptionOnRivException($json);
        self::throwExceptionOnHafasErrorCode($json);
        return $json;
    }

    /**
     * @param mixed $json
     * @return void
     */
    private static function throwExceptionOnRivException(mixed $json): void
    {

        if (array_key_exists('exception', $json) && str_starts_with($json['exception'], 'Hacon response time exceeded the defined timeout')) {
            throw new UpstreamServerTimeoutException('The upstream server encountered a timeout while loading data. Please try again later.');
        }
    }

    /**
     * Throw an exception if the JSON API response contains an error instead of a result.
     *
     * @param array $json The JSON response as an associative array.
     *
     */
    private static function throwExceptionOnHafasErrorCode(array $json): void
    {
        if (!key_exists('errorCode', $json)) {
            // all ok!
            return;
        }

        if ($json['errorCode'] == 'INT_ERR') {
            throw new UpstreamServerException('NMBS data is temporarily unavailable.');
        }
        if ($json['errorCode'] == 'INT_GATEWAY') {
            throw new UpstreamServerUnavailableException('NMBS data is temporarily unavailable.');
        }
        if ($json['errorCode'] == 'INT_TIMEOUT') {
            throw new UpstreamServerTimeoutException('The upstream server encountered a timeout while loading the data.');
        }
        if ($json['errorCode'] == 'SVC_NO_RESULT') {
            throw new NoResultsException('No results found');
        }
        if ($json['errorCode'] == 'SVC_LOC') {
            throw new NoResultsException('Location not found');
        }
        if ($json['errorCode'] == 'SVC_LOC_EQUAL') {
            throw new NoResultsException('Origin and destination location are the same', 400);
        }
        if ($json['errorCode'] == 'SVC_DATETIME_PERIOD' || $json['errorCode'] == 'SVC_DATATIME_PERIOD') {
            // Some versions of Hafas contain a typo in this error code
            throw new NoResultsException('Date outside of the timetable period. Check your query.');
        }
        if ($json['errorCode'] == 'SVC_PARAM') {
            throw new UpstreamParameterException('Origin and destination location are the same');
        }
        throw new UpstreamServerException('This request failed. Please check your query. Error code ' . $json['errorCode'], 500);
    }

}