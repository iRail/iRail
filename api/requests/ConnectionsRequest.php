<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */
include_once 'Request.php';
include_once 'data/NMBS/stations.php';

class ConnectionsRequest extends Request
{
    protected $results;
    protected $from;
    protected $to;
    protected $time;
    protected $date;
    protected $timeSel;
    protected $fast;
    protected $alerts;
    protected $typeOfTransport;
    protected $journeyoptions;

    public function __construct()
    {
        parent::__construct();
        parent::setGetVar('from', '');
        parent::setGetVar('to', '');
        parent::setGetVar('results', 6);
        parent::setGetVar('date', date('dmy'));
        parent::setGetVar('time', date('Hi'));
        parent::setGetVar('timeSel', 'depart');
        parent::setGetVar('typeOfTransport', 'train');
        parent::setGetVar('fast', 'false');
        parent::setGetVar('alerts', 'false');
        parent::processRequiredVars(['from', 'to']);

        // reform date and time to wanted structure for hafas and railtime
        preg_match('/(..)(..)(..)/si', $this->date, $m);
        $this->date = '20'.$m[3].$m[2].$m[1];
        preg_match('/(..)(..)/si', $this->time, $m);
        $this->time = $m[1].':'.$m[2];
    }

    /**
     * @return mixed
     */
    public function getFast()
    {
        return $this->fast;
    }
    
    /**
     * @return mixed
     */
    public function getAlerts()
    {
        return $this->alerts == 'true';
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * When we have found a better description of the from, let the request know
     * @param $from is a departure station 
     */
    public function setFrom($from)
    {
        $from = stations::transformOldToNewStyle($from);
        //save the original text search string
        $from['query'] = $this->from;
        $this->from = $from;
    }
    
    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * When we have found a better description of the $to, let the request know
     * @param $to a destination station 
     */
    public function setTo($to)
    {
        $to = stations::transformOldToNewStyle($to);
        //save the original text search string
        $to['query'] = $this->to;
        $this->to = $to;
    }
    
    /**
     * Get the journey options
     * @return array
     */
    public function getJourneyOptions()
    {
        return $this->journeyoptions;
    }


    /**
     * Set the journey options when a result has been found. This will be stored in the logs.
     * @param $jo is an array of journey options
     */
    public function setJourneyOptions($jo)
    {
        $this->journeyoptions = $jo;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getTimeSel()
    {
        return $this->timeSel;
    }

    /**
     * @return mixed
     */
    public function getTypeOfTransport()
    {
        return $this->typeOfTransport;
    }
}
