<?php

/**
 * This is the data structure for a request. If we get more arguments, we will be able to add those here.
 *
 * @author pieterc
 */
ini_set("include_path", ".:../:api/DataStructs:DataStructs:api/:../includes:includes");
include_once("Request.php");
include_once("InputHandlers/BRailConnectionInput.php");
include_once("InputHandlers/NSConnectionInput.php");
include_once("OutputHandlers/JSONConnectionOutput.php");
include_once("OutputHandlers/XMLConnectionOutput.php");

class ConnectionRequest extends Request {
    private $results;
    private $from;
    private $to;
    private $time;
    private $date;
    private $timeSel;
    private $typeOfTransport;
    function __construct($from, $to, $time, $date, $timeSel, $results = 6, $lang = "EN", $format="xml", $typeOfTransport = "all"){
        parent::__construct($format, $lang);
        if($from == "" || $to == ""){
            throw new Exception("No stations specified");
        }//TODO: check on input
        $this->results = $results;
        $this->from = $from;
        $this->to = $to;
        $this->time = $time;
        $this->date = $date;
        $this->timeSel = $timeSel;
        $this->typeOfTransport = $typeOfTransport;
        //check for ID
        if(sizeof(explode(".",$from)) > 1){
            $si = new StationsInput();
            $station = $si ->getStationFromId($from, $this);
            $this->from = $station ->getName();
        }
        if(sizeof(explode(".",$to)) > 1){
            $si = new StationsInput();
            $station = $si ->getStationFromId($to, $this);
            $this->to = $station ->getName();
        }
    }

    /**
     * This function serves as a factory method
     * It provides something with an input
     * @return Input
     */
    public function getInput(){
        if(parent::getCountry() == "nl"){
            return new NSConnectionInput();
        }else if(parent::getCountry()=="be"){
            return new BRailConnectionInput();
        }else{
            return new NSConnectionInput();
        }
    }
    
    public function getOutput($connections){
        if(parent::getFormat() == "xml"){
            return new XMLConnectionOutput(parent::getLang(), $connections);
        }else if(parent::getFormat() == "json"){
            return new JSONConnectionOutput(parent::getLang(), $connections);
        }else{
            throw new Exception("No outputformat specified");
        }
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

    public function getTypeOfTransport() {
        return $this->typeOfTransport;
    }


}
?>
