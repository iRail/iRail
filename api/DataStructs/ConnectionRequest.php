<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */

include_once("Request.php");
class ConnectionRequest extends Request {
    private $results;
    private $from;
    private $to;
    private $time;
    private $date;
    private $timeSel;
    private $lang;
    private $typeOfTransport;
    function __construct($from, $to, $time, $date, $timeSel, $results = 6, $lang = "EN", $typeOfTransport = "all"){
        if($from == "" || $to == ""){
            throw new Exception("No stations specified");
        }//TODO: check on input
        $this->results = $results;
        $this->from = $from;
        $this->to = $to;
        $this->time = $time;
        $this->date = $date;
        $this->timeSel = $timeSel;
        $this->lang = $lang;
        $this->typeOfTransport = $typeOfTransport;
    }

    public function getResults() {
        return $this->results;
    }

    public function getFrom() {
        return $this->from;
    }

    public function getTo() {
        return $this->to;
    }

    public function getTime() {
        return $this->time;
    }

    public function getDate() {
        return $this->date;
    }

        public function getTimeSel() {
        return $this->timeSel;
    }

    public function getLang() {
        return $this->lang;
    }

    public function getTypeOfTransport() {
        return $this->typeOfTransport;
    }


}
?>
