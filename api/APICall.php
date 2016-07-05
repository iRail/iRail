<?php
  /* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class foresees in basic HTTP functionality. It will get all the GET vars and put it in a request.
   * This requestobject will be given as a parameter to the DataRoot object, which will fetch the data and will give us a printer to print the header and body of the HTTP response.
   *
   * @author Pieter Colpaert
   */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

ini_set('include_path', '.:data');
include_once 'data/DataRoot.php';
include_once 'data/structs.php';
class APICall
{
    private $VERSION = 1.1;

    protected $request;
    protected $dataRoot;
    protected $log;
    /**
     * @param $functionname
     */
    public function __construct($resourcename)
    {
        //When the HTTP request didn't set a User Agent, set it to a blank
        if (! isset($_SERVER['HTTP_USER_AGENT'])) {
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
            $requestname = ucfirst(strtolower($resourcename)).'Request';
            include_once "requests/$requestname.php";
            $this->request = new $requestname();
            $this->dataRoot = new DataRoot($resourcename, $this->VERSION, $this->request->getFormat());
        } catch (Exception $e) {
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
        if ($format == '') {
            $format = 'Xml';
        }
        $format = ucfirst(strtolower($format));
        if (isset($_GET['callback']) && $format == 'Json') {
            $format = 'Jsonp';
        }
        if (! file_exists("output/$format.php")) {
            $format = 'Xml';
        }
        include_once "output/$format.php";
        $printer = new $format(null);
        $printer->printError($e->getCode(), $e->getMessage());
        exit(0);
    }

    public function executeCall()
    {
        try {
            $this->dataRoot->fetchData($this->request, $this->request->getSystem());
            $this->dataRoot->printAll();
            $this->writeLog();
        } catch (Exception $e) {
            $this->buildError($e);
        }
    }

    /**
     * @param Exception $e
     */
    protected function logError(Exception $e)
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
            if ($this->resourcename === 'connections') {
                $query['departureStop'] = $this->request->getFrom();
                $query['arrivalStop'] = $this->request->getTo();
                //transform to ISO8601
                $query['dateTime'] = preg_replace('/(\d\d\d\d)(\d\d)(\d\d)/i', '$1-$2-$3', $this->request->getDate()) . 'T' . $this->request->getTime() . ':00' . '+01:00';
                $query['journeyoptions'] = $this->request->getJourneyOptions();
            } elseif ($this->resourcename === 'liveboard') {
                $query['dateTime'] = preg_replace('/(\d\d\d\d)(\d\d)(\d\d)/i', '$1-$2-$3', $this->request->getDate()) . 'T' . $this->request->getTime() . ':00' . '+01:00';
                $query['departureStop'] = $this->request->getStation();
            } elseif ($this->resourcename === 'vehicleinformation') {
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
             'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ]);
     }
}
