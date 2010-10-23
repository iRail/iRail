<?php

/**
 * Description of LiveboardRequest
 *
 * @author pieterc
 */
include_once("Request.php");
class LiveboardRequest implements Request{
    private $station;
    private $date;
    private $time;
    private $arrdep;
    private $lang;

    function __construct($station, $date, $time, $arrdep = "DEP", $lang = "EN") {
        $this->station = $station;
        $this->date = $date;
        $this->time = $time;
        $this->arrdep = $arrdep;
        $this->lang = $lang;
    }

    public function getStation() {
        return $this->station;
    }

    public function getDate() {
        return $this->date;
    }

    public function getTime() {
        return $this->time;
    }

    public function getArrdep() {
        return $this->arrdep;
    }

    public function getLang() {
        return $this->lang;
    }
    
}
?>
