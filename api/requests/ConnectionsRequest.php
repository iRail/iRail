<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */
include_once("Request.php");

class ConnectionsRequest extends Request
{
    protected $results;
    protected $from;
    protected $to;
    protected $time;
    protected $date;
    protected $timeSel;
    protected $fast;
    protected $typeOfTransport;


    /**
     * Class constructor
     *
     * @throws Exception
     */
    function __construct()
    {
        parent::__construct();
        parent::setGetVar("from", "");
        parent::setGetVar("to", "");
        parent::setGetVar("results", 6);
        parent::setGetVar("date", date("dmy"));
        parent::setGetVar("time", date("Hi"));
        parent::setGetVar("timeSel", "depart");
        parent::setGetVar("typeOfTransport", "train");
        parent::setGetVar("fast", "false");
        parent::processRequiredVars(array("from", "to"));

//reform date and time to wanted structure for hafas and railtime
        preg_match("/(..)(..)(..)/si", $this->date, $m);
        $this->date = "20" . $m[3] . $m[2] . $m[1];
        preg_match("/(..)(..)/si", $this->time, $m);
        $this->time = $m[1] . ":" . $m[2];
    }

    /**
     * getFast()
     *
     * @return mixed
     */
    public function getFast()
    {
        return $this->fast;
    }

    /**
     * getResults()
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * getFrom()
     *
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * getTo()
     *
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * getTime()
     *
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * getdata()
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * getTimeSel()
     *
     * @return mixed
     */
    public function getTimeSel()
    {
        return $this->timeSel;
    }

    /**
     * getTypeOfTransport
     *
     * @return mixed
     */
    public function getTypeOfTransport()
    {
        return $this->typeOfTransport;
    }


}
