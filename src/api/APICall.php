<?php

/* Copyright (C) 2011 by iRail vzw/asbl
 * © 2015 by Open Knowledge Belgium vzw/asbl
 *
 * This class foresees in basic HTTP functionality. It will get all the GET vars and put it in a request.
 * This requestobject will be given as a parameter to the DataRoot object, which will fetch the data and will give us a printer to print the header and body of the HTTP response.
 *
 * @author Pieter Colpaert
 */

namespace Irail\api;

use Irail\api\data\DataRoot;
use Irail\api\output\Printer;
use Irail\api\requests\CompositionRequest;
use Irail\api\requests\ConnectionsRequest;
use Irail\api\requests\DisturbancesRequest;
use Irail\api\requests\LiveboardRequest;
use Irail\api\requests\StationsRequest;
use Irail\api\requests\VehicleinformationRequest;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class APICall
{
    const SUPPORTED_FILE_FORMATS = ['Json', 'Jsonp', 'Kml', 'Xml'];
    private $VERSION = 1.1;

    protected $request;
    protected $dataRoot;
    protected $log;

    /**
     * @param $resourcename
     */
    public function __construct($resourcename)
    {
        //When the HTTP request didn't set a User Agent, set it to a blank
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = '';
        }
        //Default timezone is Brussels
        date_default_timezone_set('Europe/Brussels');
        //This is the current resource that's handled. E.g., stations, connections, vehicleinfo or liveboard
        $this->resourcename = $resourcename;
        try {
            $this->log = new Logger('irapi');
            //Create a formatter for the logs
            $logFormatter = new LineFormatter("%context%\n", 'Y-m-d\TH:i:s');
            $streamHandler = new StreamHandler(__DIR__ . '/../storage/irapi.log', Logger::INFO);
            $streamHandler->setFormatter($logFormatter);
            $this->log->pushHandler($streamHandler);
            switch (strtolower($resourcename)) {
                case "composition":
                    $this->request = new CompositionRequest();
                    $rootname = "Composition";
                    break;
                case "connections":
                    $this->request = new ConnectionsRequest();
                    $rootname = "Connections";
                    break;
                case "disturbances":
                    $this->request = new DisturbancesRequest();
                    $rootname = "Disturbances";
                    break;
                case "liveboard":
                    $this->request = new LiveboardRequest();
                    $rootname = "Liveboard";
                    break;
                case "stations":
                    $this->request = new StationsRequest();
                    $rootname = "Stations";
                    break;
                case "vehicleinformation":
                    $this->request = new VehicleinformationRequest();
                    $rootname = "Vehicleinformation";
                    break;
                default:
                    throw new \Exception("Invalid request type", 400);
            }
            $this->dataRoot = new DataRoot($rootname, $this->VERSION, $this->request->getFormat());
        } catch (\Exception $e) {
            $this->buildError($e);
        }
    }

    /**
     * @param $e
     */
    private function buildError($e)
    {
        $this->logError($e);
        //Build a nice output
        $format = '';
        if (isset($_GET['format'])) {
            $format = $_GET['format'];
        }
        $printer = Printer::getPrinterInstance($format, null);
        $printer->printError($e->getCode(), $e->getMessage());
        exit(0);
    }

    public function executeCall()
    {
        try {
            $this->dataRoot->fetchData($this->request);
            $this->dataRoot->printAll();
            $this->writeLog();
        } catch (\Exception $e) {
            $this->buildError($e);
        }
    }

    /**
     * @param \Exception $e
     */
    protected function logError(\Exception $e)
    {
        $query = $this->getQuery();
        if ($e->getCode() >= 500) {
            $this->log->addCritical($this->resourcename, [
                "querytype" => $this->resourcename,
                "error" => $e->getMessage(),
                "code" => $e->getCode(),
                "query" => $query
            ]);
        } else {
            $this->log->addError($this->resourcename, [
                "querytype" => $this->resourcename,
                "error" => $e->getMessage(),
                "code" => $e->getCode(),
                "query" => $query
            ]);
        }
    }

    private function getQuery()
    {
        if (isset($this->request)) {
            $query = [
                'serialization' => $this->request->getFormat(),
                'language' => $this->request->getLang()
            ];
            if ($this->resourcename === 'Connections') {
                $query['departureStop'] = $this->request->getFrom();
                $query['arrivalStop'] = $this->request->getTo();
                //transform to ISO8601
                $query['dateTime'] = preg_replace(
                    '/(\d\d\d\d)(\d\d)(\d\d)/i',
                    '$1-$2-$3',
                    $this->request->getDate()
                ) . 'T' . $this->request->getTime() . ':00' . '+01:00';
                $query['journeyoptions'] = $this->request->getJourneyOptions();
            } elseif ($this->resourcename === 'liveboard') {
                $query['dateTime'] = preg_replace(
                    '/(\d\d\d\d)(\d\d)(\d\d)/i',
                    '$1-$2-$3',
                    $this->request->getDate()
                ) . 'T' . $this->request->getTime() . ':00' . '+01:00';
                $query['departureStop'] = $this->request->getStation();
            } elseif ($this->resourcename === 'VehicleInformation') {
                $query['vehicle'] = $this->request->getVehicleId();
            }
            return $query;
        } else {
            //If we were unable to retrieve the right parameters, just return the GET parameters in the request
            return $_GET;
        }
    }

    /**
     * Writes an entry to the log in level "INFO"
     */
    protected function writeLog()
    {
        $query = $this->getQuery();
        $this->log->addInfo($this->resourcename, [
            'querytype' => $this->resourcename,
            'querytime' => date('c'),
            'query' => $query,
            'user_agent' => $this->maskEmailAddress($_SERVER['HTTP_USER_AGENT']),
        ]);
    }

    /**
     * Obfuscate an email address in a user agent. abcd@defg.be becomes a***@d***.be.
     *
     * @param $userAgent
     * @return string
     */
    private function maskEmailAddress($userAgent): string
    {
        // Extract information
        $hasMatch = preg_match("/([^\(\) @]+)@([^\(\) @]+)\.(\w{2,})/", $userAgent, $matches);
        if (!$hasMatch) {
            // No mail address in this user agent.
            return $userAgent;
        }
        $mailReceiver = substr($matches[1], 0, 1) . str_repeat('*', strlen($matches[1]) - 1);
        $mailDomain = substr($matches[2], 0, 1) . str_repeat('*', strlen($matches[2]) - 1);

        $obfuscatedAddress = $mailReceiver . '@' . $mailDomain . '.' . $matches[3];

        $userAgent = preg_replace("/([^\(\) ]+)@([^\(\) ]+)\.(\w{2,})/", $obfuscatedAddress, $userAgent);
        return $userAgent;
    }
}
