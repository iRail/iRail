<?php

namespace Irail\Proxy;

use Carbon\Carbon;

class CurlHttpResponse
{
    private string $method;
    private string $url;
    private ?string $requestBody;
    private int $responseCode;
    private ?string $responseBody;
    private int $duration;
    private Carbon $timestamp;

    /**
     * @param Carbon $timestamp int The epoch timestamp in milliseconds
     * @param string $method
     * @param string      $url
     * @param string|null $requestBody
     * @param int         $responseCode
     * @param string|null $responseBody
     * @param int    $duration
     */
    public function __construct(Carbon $timestamp, string $method, string $url, ?string $requestBody, int $responseCode, ?string $responseBody, int $duration)
    {
        $this->timestamp = $timestamp;
        $this->url = $url;
        $this->requestBody = $requestBody;
        $this->responseCode = $responseCode;
        $this->responseBody = $responseBody;
        $this->method = $method;
        $this->duration = $duration;
    }

    /**
     * @return Carbon The timestamp on which the request was made
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function getRequestBody(): ?string
    {
        return $this->requestBody;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @return string|null
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    public function toString()
    {
        return "{$this->responseCode} {$this->method} {$this->url} {$this->duration}ms {$this->requestBody} {$this->getResponseBody()}";
    }

    public function toFormattedString()
    {
        return "{$this->responseCode} [{$this->method}] {$this->url} ({$this->duration} ms)\n\n" . ($this->requestBody ?: 'no request body') . "\n\n" . $this->getResponseBody() ?: 'no response body';
    }
}
