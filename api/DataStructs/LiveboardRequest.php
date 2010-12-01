<?php

/**
 * Description of LiveboardRequest
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:../includes:includes");
include_once("Request.php");
include_once("InputHandlers/BRailLiveboardInput.php");
include_once("InputHandlers/NSLiveboardInput.php");
include_once("OutputHandlers/JSONLiveboardOutput.php");
include_once("OutputHandlers/XMLLiveboardOutput.php");

class LiveboardRequest extends Request{
    private $station;
    private $date;
    private $time;
    private $arrdep;

    function __construct($station, $date, $time, $arrdep = "DEP", $lang = "EN", $format = "xml", $isId = false) {
        parent::__construct($format, $lang);
        if(!$isId){
            $this->station = $station;
        }else{
            $stin = new StationsInput();
            $this->station = $stin ->getStationFromId($station, $this) ->getName();
        }
        $this->date = $date;
        $this->time = $time;
        $this->arrdep = $arrdep;
    }

    /**
     * This function serves as a factory method
     * It provides something with an input
     * @return Input
     */
    public function getInput(){
        if(parent::getCountry() == "nl"){
            return new NSLiveboardInput();
        }else if(parent::getCountry()=="be"){
            return new BRailLiveBoardInput();
        }else{
            return new NSLiveboardInput();
        }
    }
    public function getOutput($l){
        if(parent::getFormat() == "xml"){
            return new XMLLiveboardOutput(parent::getLang() ,$l);
        }else if(parent::getFormat() == "json"){
            return new JSONLiveboardOutput(parent::getLang() ,$l);
        }else{
            throw new Exception("No outputformat specified");
        }
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
    
}
?>
