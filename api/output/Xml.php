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
     private $ATTRIBUTES=array("id", "locationX", "locationY", "standardname", "left","delay", "normal");

     function printHeader(){
	  header("Access-Control-Allow-Origin: *");
	  header("Content-Type: text/xml");
     }

     function printError($ec, $msg){
	  $this->printHeader();
	  header("HTTP/1.1 $ec $msg");
	  echo "<error code=\"$ec\">$msg</error>";
     }
     
     function startRootElement($name, $version, $timestamp){
	  echo "<$name version=\"$version\" timestamp=\"$timestamp\">";
     }

     function startArray($name,$number, $root = false){
	  if(!$root){
	       echo "<".$name."s number=\"$number\">";
	  }
     }

     function startObject($name, $object){
	  echo "<$name";
          //fallback for attributes and name tag
	  $hash = get_object_vars($object);
	  $named = "";
	  foreach($hash as $elementkey => $elementval){
	       if(in_array($elementkey, $this->ATTRIBUTES)){
		    echo " $elementkey=\"$elementval\"";
	       }else if($elementkey == "name"){
		    $named = $elementval;
	       }
	  }
	  echo ">";
	  if($named != ""){
	       echo $named;
	  }
	  $nameObjectStarted = $name;
     }

     function startKeyVal($key,$val){
	  if($key == "time"){
	       $form = $this->iso8601($val);
	       echo "<$key formated=\"$form\">$val";
	  }else if($key != "name" && !in_array($key,$this->ATTRIBUTES)){
	       echo "<$key>$val";
	  }
     }

     function endElement($name){
	  if(!in_array($name, $this->ATTRIBUTES) && $name != "name"){
	       echo "</$name>";
	  }
     }
     function endArray($name, $root = false){
	  if(!$root){
	       echo "</".$name."s>";
	  }	  
     }

     function endRootElement($name){
	  echo "</$name>";
     }

     function iso8601($unixtime){
	  return date("Y-m-d\TH:i:s\Z", $unixtime);
     }

};
?>