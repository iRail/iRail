<?php
  /* Copyright (C) 2011 by iRail vzw/asbl */
include_once("Printer.php");

/**
 * Prints the Kml style output. This works only for stations!!!
 *
 * Todo: change in_array to isset key lookups. This should make the whole faster
 *
 * @package output
 */
class Kml extends Printer{
     private $ATTRIBUTES=array("id", "locationX", "locationY", "standardname", "left","delay", "normal");
     private $rootname;


     function printHeader(){
	  header("Access-Control-Allow-Origin: *");
	  header("Content-Type: application/vnd.google-earth.kml+xml");
	  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	  
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
	  if($name == "stations"){
	       echo "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
	  }else{
	       $this->printError(400,"KML only works for stations at this moment");
	  }
     }
//make a stack of array information, always work on the last one
//for nested array support
     private $stack = array();
     private $arrayindices = array();
     private $currentarrayindex = -1;

    /**
     * @param $name
     * @param $number
     * @param bool $root
     */
    function startArray($name,$number, $root = false){
     }

     function nextArrayElement(){
	  $this->arrayindices[$this->currentarrayindex]++;
     }

    /**
     * @param $name
     * @param $object
     */
    function startObject($name, $object){
	  if($name == "station"){
	       echo "<Placemark id='". $object->id ."'><name>". $object->name ."</name><Point><coordinates>". $object->locationX .",". $object->locationY."</coordinates></Point></Placemark>";
	  }
     }

    /**
     * @param $key
     * @param $val
     */
    function startKeyVal($key,$val){
     }

    /**
     * @param $name
     */
    function endElement($name){
     }

    /**
     * @param $name
     * @param bool $root
     */
    function endArray($name, $root = false){
     }

    /**
     * @param $name
     */
    function endRootElement($name){
	  echo "</kml>";
     }
};
?>