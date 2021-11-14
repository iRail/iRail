<?php

/* Copyright (C) 2011 by iRail vzw/asbl
 *
 * This is an interface to a Request
 *
 * @author pieterc
 */

namespace Irail\Http\Requests;

use DateTime;
use Exception;
use Illuminate\Http\Request as LumenRequest;

abstract class HttpRequest extends LumenRequest
{
    private const SUPPORTED_LANGUAGES = ['en', 'nl', 'fr', 'de'];
    private const SUPPORTED_FORMATS = ['json', 'xml'];

    private string $responseFormat = 'xml';
    private string $language;
    private bool $debug = false;

    public function __construct()
    {
        parent::__construct();
        $this->determineResponseFormat();
        $this->determineLanguage();
    }

    /**
     * @param String[] names of required parameters
     * @throws Exception
     */
    protected function verifyRequiredVariablesPresent(array $array)
    {
        foreach ($array as $var) {
            if (!$this->has($var)) {
                throw new Exception("$var not set. Please review your request and add the right parameters", 400);
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

    public function isDebugModeEnabled()
    {
        return $this->debug;
    }

    private function determineResponseFormat(): void
    {
        $this->responseFormat = $this->input('format', 'json');
        $this->responseFormat = strtolower($this->responseFormat);
        if (!in_array($this->responseFormat, self::SUPPORTED_FORMATS)) {
            throw new Exception("Format {$this->responseFormat} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_FORMATS), 400);
        }
    }

    /**
     * @throws Exception thrown when an invalid language is provided
     */
    private function determineLanguage(): void
    {
        $this->language = $this->input('lang', 'en');
        $this->language = strtolower($this->language);
        if (!in_array($this->language, self::SUPPORTED_LANGUAGES)) {
            throw new Exception("Language {$this->language} is not supported. Allowed values are: "
                . join(', ', self::SUPPORTED_LANGUAGES), 400);
        }
    }


    /**
     * Get a 9-digit numeric station id.
     *
     * @param $id
     * @return string
     */
    protected function parseStationId($id): string
    {
        if (strlen($id) == 9) {
            // iRail style
            return $id;
        }
        if (strlen($id) == 7) {
            // GTFS and HAFAS style
            return '00' . $id;
        }
        if (str_starts_with($id, "http://irail.be/stations/NMBS/")) {
            // iRail URI
            return substr($id, 30);
        }
        throw new Exception("The provided station id {$id} is invalid.", 400);
    }


    /**
     * @throws Exception when the provided datetime is not in a valid format
     */
    protected function parseDateTime($datetime)
    {
        try {
            return new DateTime($datetime);
        } catch (Exception $e) {
            throw new Exception("The provided date/time {$this->get('datetime')} is invalid.", 400, $e);
        }
    }
}
