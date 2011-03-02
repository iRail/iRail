<?php
  /* Copyright (C) 2011 by iRail vzw/asbl */
  /**
   * Prints the Json style output
   *
   *
   * @package output
   */
include_once("Printer.php");
class Json extends Printer{
     private $rootname;
     
     function printHeader(){
	  header("Access-Control-Allow-Origin: *");
	  header("Content-Type: application/json;charset=UTF-8");
     }

     function printError($ec, $msg){
	  $this->printHeader();
	  header("HTTP/1.1 $ec $msg");
	  echo "{\"error\":$ec,\"message\":\"$msg\"}";
     }

     function startRootElement($name, $version, $timestamp){
	  $this->rootname = $name;
	  echo "{\"version\":\"$version\",\"timestamp\":\"$timestamp\",";
     }

//make a stack of array information, always work on the last one
//for nested array support
     private $stack = array();
     private $arrayindices = array();
     private $currentarrayindex = -1;
     function startArray($name,$number, $root = false){
	  if(!$root || $this->rootname == "liveboard"){
	       echo "\"" . $name . "s\":{\"number\":\"$number\",";
	  }
	  echo "\"$name\":[";
	  $this->currentarrayindex ++;
	  $this->stack[$this->currentarrayindex] = $name;
	  $this->arrayindices[$this->currentarrayindex] = 0;
     }

     function nextArrayElement(){
	  echo ",";
	  $this->arrayindices[$this->currentarrayindex]++;
     }

     function nextObjectElement(){
	  echo ",";
     }

     function startObject($name, $object){
	  if($this->currentarrayindex >-1 && $this->stack[$this->currentarrayindex] == $name){
	       echo "{";
//show id (in array) except if array of stations (compatibility issues)
	       if($name != "station"){
		    echo "\"id\":\"".$this->arrayindices[$this->currentarrayindex]."\",";
	       }
	  }
	  else{
	       if($this->rootname != "stations" && $name == "station"){ // split station into station and stationinfo to be compatible with 1.0
		    echo "\"$name\":\"$object->name\",";
		    echo "\"". $name. "info\":{";
	       }else{
		    echo "\"$name\":{";
	       }
	       
	  }
	  
     }

     function startKeyVal($key,$val){
	  echo "\"$key\":\"$val\"";
     }

     function endArray($name, $root = false){
	  $this->stack[$this->currentarrayindex] = "";
	  $this->arrayindices[$this->currentarrayindex] = 0;
	  $this->currentarrayindex --;
	  if($root && $this->rootname != "liveboard"){
	       echo "]";
	  }else{
	       echo "]}";
	  }
     }
     
     function endObject($name){
	  echo "}";
     }
     
     function endElement($name){
	  
     }

     function endRootElement($name){
	  echo "}";
     }
};

?>