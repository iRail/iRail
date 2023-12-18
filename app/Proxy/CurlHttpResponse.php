<?php

namespace Irail\Proxy;

class CurlHttpResponse
{
    private string $method;
    private string $url;
    private ?string $requestBody;
    private int $responseCode;
    private ?string $responseBody;
    private int $duration;

    /**
     * @param string      $url
     * @param string|null $requestBody
     * @param int         $responseCode
     * @param string|null $responseBody
     */
    public function __construct(string $method, string $url, ?string $requestBody, int $responseCode, ?string $responseBody, int $duration)
    {
        $this->url = $url;
        $this->requestBody = $requestBody;
        $this->responseCode = $responseCode;
        $this->responseBody = $responseBody;
        $this->method = $method;
        $this->duration = $duration;
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