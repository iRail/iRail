<?php
  /* Copyright (C) 2011 by iRail vzw/asbl */
include_once("Printer.php");

/**
 * Prints the Xml style output
 *
 * Todo: change in_array to isset key lookups. This should make the whole faster
 *
 * @package output
 */
class Xml extends Printer{
    private $ATTRIBUTES=array("id", "@id", "locationX", "locationY", "standardname", "left","delay", "normal");
     private $rootname;

     function printHeader(){
	  header("Access-Control-Allow-Origin: *");
	  header("Content-Type: text/xml; charset=UTF-8");
     }

    /**
     * @param $ec
     * @param $msg
     */
    function printError($ec, $msg){
	  $this->printHeader();
	  header("HTTP/1.1 $ec $msg");
	  echo "<error code=\"$ec\">$msg</error>";
     }

    /**
     * @param $name
     * @param $version
     * @param $timestamp
     */
    function startRootElement($name, $version, $timestamp){
	  $this->rootname = $name;
	  echo "<$name version=\"$version\" timestamp=\"$timestamp\">";
     }
//make a stack of array information, always work on the last one
//for nested array support
     private $stack = array();
     private $arrayindices = array();
     private $currentarrayindex = -1;
     function startArray($name,$number, $root = false){
	  if(!$root || $this->rootname == "liveboard" || $this->rootname == "vehicleinformation"){
	       echo "<".$name."s number=\"$number\">";
	  }
	  $this->currentarrayindex ++;
	  $this->arrayindices[$this->currentarrayindex] = 0;
	  $this->stack[$this->currentarrayindex] = $name;
     }

     function nextArrayElement(){
	  $this->arrayindices[$this->currentarrayindex]++;
     }

    /**
     * @param $name
     * @param $object
     */
    function startObject($name, $object){
//test wether this object is a first-level array object
	  echo "<$name";
	  if($this->currentarrayindex > -1 && $this->stack[$this->currentarrayindex] == $name && $name != "station") {
	       echo " id=\"".$this->arrayindices[$this->currentarrayindex]."\"";
	  }
	  //fallback for attributes and name tag
	  $hash = get_object_vars($object);
	  $named = "";
	  foreach($hash as $elementkey => $elementval){
	       if(in_array($elementkey, $this->ATTRIBUTES)){
                   if($elementkey == "@id"){
                       $elementkey = "URI";
                   }
                   echo " $elementkey=\"$elementval\"";
	       }else if($elementkey == "name"){
		    $named = $elementval;
	       }
	  }
	  echo ">";
	  if($named != ""){
	       echo $named;
	  }
	  
     }

    /**
     * @param $key
     * @param $val
     */
    function startKeyVal($key,$val){
	  if($key == "time"){
	       $form = $this->iso8601($val);
	       echo "<$key formatted=\"$form\">$val";
	  }else if($key != "name" && !in_array($key,$this->ATTRIBUTES)){
	       echo "<$key>$val";
	  }
     }

    /**
     * @param $name
     */
    function endElement($name){
	  if(!in_array($name, $this->ATTRIBUTES) && $name != "name"){
	       echo "</$name>";
	  }
     }

    /**
     * @param $name
     * @param bool $root
     */
    function endArray($name, $root = false){
	  if(!$root || $this->rootname == "liveboard" || $this->rootname == "vehicleinformation"){
	       echo "</".$name."s>";
	  }
	  $this->stack[$this->currentarrayindex] = "";
	  $this->arrayindices[$this->currentarrayindex] = 0;
	  $this->currentarrayindex --;
     }

    /**
     * @param $name
     */
    function endRootElement($name){
	  echo "</$name>";
     }

    /**
     * @param $unixtime
     * @return bool|string
     */
    function iso8601($unixtime){
	  return date("Y-m-d\TH:i:s\Z", $unixtime);
     }

};
