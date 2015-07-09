<?php
  /** Copyright (C) 2011 by iRail vzw/asbl
   *
   * LiveboardRequest Class
   *
   * @author pieterc
   */
include_once("Request.php");

class LiveboardRequest extends Request{
     protected $station;
     protected $date;
     protected $time;
     protected $arrdep;
     protected $fast;

     function __construct(){
	  parent::__construct();
	  parent::setGetVar("station", "");
	  parent::setGetVar("date", date("dmy"));
	  parent::setGetVar("arrdep", "DEP");
	  parent::setGetVar("time", date("Hi"));
          parent::setGetVar("fast","false");
	  preg_match("/(..)(..)(..)/si", $this->date, $m);
          if(sizeof($m) > 3){
              $this->date = "20" . $m[3] . $m[2] . $m[1];
          }else if(sizeof($m) > 2){
              $this->date = date("Y") . $m[2] . $m[1];
          }
          
	  preg_match("/(..)(..)/si", $this->time, $m);
	  $this->time = $m[1] . ":" . $m[2];
	  if($this->station == "" && isset($_GET["id"])){
	       //then there was an id given
	       $this->station = $_GET["id"];
	  }
	  parent::processRequiredVars(array("station"));
     }

     public function isFast(){
         return $this->fast == "true";
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

