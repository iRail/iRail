<?php
  /* Copyright (C) 2011 by iRail vzw/asbl
   * © 2015 by Open Knowledge Belgium vzw/asbl
   *
   * This class foresees in basic HTTP functionality. It will get all the POST vars and put it in a request.
   *
   * @author Stan Callewaert
   */

use MongoDB\Collection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
include_once 'spitsgids/SpitsgidsController.php';

class APIPost
{
    private $postData;
    private $resourcename;
    private $log;
    private $method;

    public function __construct($resourcename, $postData, $method)
    {
        //Default timezone is Brussels
        date_default_timezone_set('Europe/Brussels');

        $this->resourcename = $resourcename;
        $this->postData = json_decode($postData);
        $this->method = $method;

        try {
            $this->log = new Logger('irapi');
            //Create a formatter for the logs
            $logFormatter = new LineFormatter("%context%\n", 'Y-m-d\TH:i:s');
            $streamHandler = new StreamHandler(__DIR__ . '/../storage/irapi.log', Logger::INFO);
            $streamHandler->setFormatter($logFormatter);
            $this->log->pushHandler($streamHandler);
        } catch (Exception $e) {
            $this->buildError($e);
        }
    }

    public function writeToMongo($ip)
    {
        if($this->method == "POST") {
            try {
                if ($this->resourcename == 'occupancy') {
                    $this->occupancyToMongo($ip);
                }
            } catch (Exception $e) {
                $this->buildError($e);
            }
        } else {
            $this->buildError(new Exception('Only post requests are allowed', 405));
        }
    }

    private function occupancyToMongo($ip)
    {
        if(!is_null($this->postData->vehicle) && !is_null($this->postData->from) && !is_null($this->postData->to) && !is_null($this->postData->occupancy)) {
            try {
                $m = new MongoDB\Driver\Manager("mongodb://localhost:27017");
                $ips = new MongoDB\Collection($m, 'spitsgids', 'IPsUsersLastMinute');

                // Delete the ips who are longer there than 1 minute
                $epoch = time();
                $epochMinuteAgo = $epoch - 60;
                $ips->deleteMany(array('timestamp' => array('$lt' => $epochMinuteAgo)));

                // Find if the same IP posted the last minute
                $ipLastMinute = $ips->findOne(array('ip' => $ip));

                // If it didn't put it in the table and execute the post
                if(is_null($ipLastMinute)) {
                    $ips->insertOne(array('ip' => $ip, 'timestamp' => time()));

                    // Return a 201 message and redirect the user to the iRail api GET page of a vehicle
                    header("HTTP/1.0 201 Created");
                    header('Location: https://irail.be/vehicle/?id=BE.NMBS.' + $this->postData->vehicle);

                    $postInfo = array(
                        'vehicle' => $this->postData->vehicle,
                        'from' => $this->postData->from,
                        'to' => $this->postData->to,
                        'occupancy' => $this->postData->occupancy,
                        'date' => date('Ymd')
                    );

                    // Log the post in the iRail log file
                    array_push($postInfo, $ip);
                    $this->writeLog($postInfo);

                    SpitsgidsController::processFeedback($postInfo, $epoch);
                } else {
                    throw new Exception('Too Many Requests', 429);
                }
            } catch (Exception $e) {
                $this->buildError($e);
            }
        } else {
            throw new Exception('Incorrect post parameters, the occupancy post request must contain the following parameters: vehicle, from, to and occupancy.', 400);
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

    /**
     * @param Exception $e
     */
    private function logError(Exception $e)
    {
        if ($e->getCode() >= 500) {
            $this->log->addCritical($this->resourcename, [
                "querytype" => $this->resourcename,
                "error" => $e->getMessage(),
                "code" => $e->getCode(),
                "query" => $postData
            ]);
        } else {
            $this->log->addError($this->resourcename, [
                "querytype" => $this->resourcename,
                "error" => $e->getMessage(),
                "code" => $e->getCode(),
                "query" => $postData
            ]);
        }
    }

    /**
     * Writes an entry to the log in level "INFO"
     */
     private function writeLog($postInfo)
     {
         $this->log->addInfo($this->resourcename, [
             'querytype' => $this->resourcename,
             'querytime' => date('c'),
             'post' => $postInfo,
             'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ]);
     }
}
