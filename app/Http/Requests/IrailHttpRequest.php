<?php

/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * This is an interface to a Request
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request as LumenRequest;
use Irail\Exceptions\Request\InvalidRequestException;
use Irail\Repositories\Irail\StationsRepository;

abstract class IrailHttpRequest extends LumenRequest
{
    private const SUPPORTED_LANGUAGES = ['en', 'nl', 'fr', 'de'];
    private const SUPPORTED_FORMATS = ['json', 'xml'];

    private string $responseFormat = 'xml';
    private string $language = 'nl';
    protected $_request;

    /**
     * @throws InvalidRequestException
     */
    public function __construct()
    {
        parent::__construct();
        $this->_request = app('request');
        $this->determineResponseFormat();
        $this->determineLanguage();
        app(StationsRepository::class)->setLocalizedLanguage($this->language);
    }

    /**
     * @param String[] $array names of required parameters
     * @throws InvalidRequestException
     */
    protected function verifyRequiredVariablesPresent(array $array)
    {
        foreach ($array as $var) {
            if (!$this->has($var)) {
                throw new InvalidRequestException("$var not set. Please review your request and add the right parameters", 400);
            }
        }
    }

    /**
     * Get the requested response format, either 'json' or 'xml'
     * @return string
     */
    public function getResponseFormat(): string
    {
        return $this->responseFormat;
    }

    /**
     * Get the requested response language, as an ISO2 code.
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getApiVersion(): int
    {
        return $this->apiVersion;
    }

    public function isDebugModeEnabled(): bool
    {
        return $this->get('debug', false) == true;
    }

    /**
     * Response format for responses to v1 endpoints.
     * @return void
     * @throws InvalidRequestException
     */
    private function determineResponseFormat(): void
    {
        $this->responseFormat = $this->_request->get('format', 'xml');
        $this->responseFormat = strtolower($this->responseFormat);
        if (!in_array($this->responseFormat, self::SUPPORTED_FORMATS)) {
            throw new InvalidRequestException("Format {$this->responseFormat} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_FORMATS), 400);
        }
    }

    /**
     * @throws InvalidRequestException thrown when an invalid language is provided
     */
    private function determineLanguage(): void
    {
        $this->language = $this->_request->get('lang', 'en');
        $this->language = strtolower($this->language);
        if (!in_array($this->language, self::SUPPORTED_LANGUAGES)) {
            throw new InvalidRequestException("Language {$this->language} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_LANGUAGES), 400);
        }
    }


    /**
     * Get a 9-digit numeric station id.
     *
     * @param $id
     * @return string
     * @throws InvalidRequestException
     */
    protected function parseStationId(string $fieldName, ?string $id): string
    {
        if (!$id) {
            throw new InvalidRequestException("Could not find station, missing query parameter $fieldName.", 400);
        }
        if (strlen($id) == 9 && is_numeric($id)) {
            // iRail style
            return $id;
        }
        if (strlen($id) == 7 && is_numeric($id)) {
            // GTFS and HAFAS style
            return '00' . $id;
        }
        if (str_starts_with($id, "http://irail.be/stations/NMBS/")) {
            // iRail URI
            return substr($id, 30);
        }
        if (!is_numeric($id)) {
            $name = urldecode($id); // ensure spaces etc are decoded
            $station = app(StationsRepository::class)->findStationByName($name);
            if ($station != null) {
                return $station->getId();
            }
        }
        throw new InvalidRequestException("The provided station id {$id} is invalid.", 400);
    }


    /**
     * @throws InvalidRequestException when the provided datetime is not in a valid format
     */
    protected function parseDateTime(?string $datetime): Carbon
    {
        if (!$datetime) {
            return Carbon::now('Europe/Brussels');
        }
        try {
            return new Carbon($datetime, 'Europe/Brussels');
        } catch (Exception $e) {
            throw new InvalidRequestException("The provided date/time {$datetime} is invalid.", 400, $e);
        }
    }

    /**
     * @throws InvalidRequestException
     */
    protected function parseDepartureArrival(string $value): TimeSelection
    {
        if (strtolower($value) === 'departure') {
            return TimeSelection::DEPARTURE;
        }
        if (strtolower($value) === 'arrival') {
            return TimeSelection::ARRIVAL;
        }
        throw new InvalidRequestException("The provided time mode selection {$value} is invalid.", 400);
    }

    /**
     * @param string $param
     * @return string|null
     */
    protected function routeOrGet(string $param): ?string
    {
        return $this->_request->route($param, $this->_request->get($param));
    }
}
