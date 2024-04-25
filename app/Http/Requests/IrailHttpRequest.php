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
use Illuminate\Support\Facades\Log;
use Irail\Exceptions\Request\InvalidRequestException;
use Irail\Exceptions\Request\RequestedStopNotFoundException;
use Irail\Repositories\Irail\StationsRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class IrailHttpRequest extends LumenRequest
{
    private const SUPPORTED_LANGUAGES = ['en', 'nl', 'fr', 'de'];
    private const SUPPORTED_FORMATS = ['json', 'xml'];

    private string $responseFormat = 'xml';
    private string $language = 'nl';

    protected LumenRequest $_request;

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidRequestException
     * @throws NotFoundExceptionInterface
     */
    public function __construct()
    {
        parent::__construct();
        $this->_request = app('request');
        $this->determineResponseFormat();
        $this->determineLanguage();
        $this->initializeStationNameLanguage();
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

    public function getUserAgent(): string
    {
        return $this->_request->header('User-Agent') ?: $this->_request->header('user-agent');
    }

    public function isDebugModeEnabled(): bool
    {
        return $this->_request->get('debug') == true;
    }

    /**
     * Response format for responses to v1 endpoints.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidRequestException
     * @throws NotFoundExceptionInterface
     */
    private function determineResponseFormat(): void
    {
        $this->responseFormat = $this->_request->get('format') ?: 'xml';
        $this->responseFormat = strtolower($this->responseFormat);
        if (!in_array($this->responseFormat, self::SUPPORTED_FORMATS)) {
            throw new InvalidRequestException("Format {$this->responseFormat} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_FORMATS));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidRequestException thrown when an invalid language is provided
     * @throws NotFoundExceptionInterface
     */
    private function determineLanguage(): void
    {
        $this->language = $this->_request->get('lang', 'en');
        $this->language = strtolower($this->language);
        if (!in_array($this->language, self::SUPPORTED_LANGUAGES)) {
            throw new InvalidRequestException("Language {$this->language} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_LANGUAGES));
        }
    }

    /**
     * @return void
     */
    public function initializeStationNameLanguage(): void
    {
        /** @var StationsRepository $stationsRepository */
        $stationsRepository = app(StationsRepository::class);
        $stationsRepository->setLocalizedLanguage($this->language);
    }

    /**
     * Get a 9-digit numeric station id.
     *
     * @param string      $fieldName
     * @param string|null $id
     * @return string
     * @throws InvalidRequestException
     */
    protected function parseStationId(string $fieldName, ?string $id): string
    {
        if (!$id) {
            throw new InvalidRequestException("Could not find station, missing query parameter $fieldName.");
        }
        if (strlen($id) == 9 && is_numeric($id)) {
            // iRail style
            return $id;
        }
        if (strlen($id) == 7 && is_numeric($id)) {
            // GTFS and HAFAS style
            return '00' . $id;
        }
        if (str_starts_with($id, 'http://irail.be/stations/NMBS/')) {
            // iRail URI
            return substr($id, 30);
        }
        if (str_starts_with($id, 'BE.NMBS.')) {
            // Old irail id style
            return substr($id, 8);
        }
        if (!is_numeric($id)) {
            try {
                $name = urldecode($id); // ensure spaces etc are decoded
                $station = app(StationsRepository::class)->findStationByName($name);
                if ($station != null) {
                    return $station->getId();
                }
            } catch (Exception $ignored) {

            }
        }
        throw new RequestedStopNotFoundException($id);
    }


    /**
     * @return Carbon
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function parseIrailV1DateTime(): Carbon
    {
        try {
            $defaultDateTime = Carbon::now('Europe/Brussels');
            $date = $this->_request->get('date') ?: $defaultDateTime->format('dmy');
            $time = $this->_request->get('time') ?: $defaultDateTime->format('Hi');
            if (strlen($date) == 6) {
                $date = substr($date, 0, 4) . '20' . substr($date, 4);
            }
            if (strlen($time) == 3) {
                $time = '0' . $time;
            }
            return Carbon::createFromFormat('dmY Hi', $date . ' ' . $time);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new InvalidRequestException('Invalid date/time provided');
        }
    }


    /**
     * @throws InvalidRequestException when the provided datetime is not in a valid format
     */
    protected function parseDateTime(?string $datetime, ?string $format = null): Carbon
    {
        if (!$datetime) {
            return Carbon::now('Europe/Brussels');
        }
        try {
            if ($format != null) {
                return Carbon::createFromFormat($format, $datetime, 'Europe/Brussels');
            }
            return new Carbon($datetime, 'Europe/Brussels');
        } catch (Exception $e) {
            throw new InvalidRequestException("The provided date/time {$datetime} is invalid.");
        }
    }

    /**
     * @throws InvalidRequestException
     */
    protected function parseV1DepartureArrival(string $value): TimeSelection
    {
        if (strtolower($value[0]) == 'a') {
            return TimeSelection::ARRIVAL;
        }
        return TimeSelection::DEPARTURE;
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
        throw new InvalidRequestException("The provided time mode selection '{$value}' is invalid. Should be one of 'departure', 'arrival'.");
    }

    /**
     * @param string      $param
     * @param string|null $defaultValue
     * @return string|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function routeOrGet(string $param, string $defaultValue = null): ?string
    {
        return $this->_request->route($param, $this->_request->get($param)) ?: $defaultValue;
    }
}
