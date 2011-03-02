<?php

  /**
   * An abstract class for a printer. It prints a document
   *
   * @package output
   */
abstract class Printer{
     protected $documentRoot;
     
     function __construct($documentRoot){
	  $this->documentRoot = $documentRoot;
     }
     
     function printAll(){
	  $this->printHeader();
	  $this->printBody();
     }     
/**
 * prints http header: what kind of output, etc
 *
 * @param string format a mime type
 */
     abstract function printHeader();

     protected $root;
     
/**
 * prints the body: The idea begind this is a reversed sax-parser. It will create events which you will have to implement in your implementation of an output.
 */
     function printBody(){
          //so that people would know that we have a child of the rootelement
	  $this->root = true;
	  $this->startRootElement($this->documentRoot->getRootname(), $this->documentRoot->version, $this->documentRoot->timestamp);
	  $hash = get_object_vars($this->documentRoot);
	  $counter = 0;
	  foreach($hash as $key => $val){
	       if($key == "version" || $key == "timestamp") {
		    $counter++;
		    continue;
	       }
	       $this->printElement($key,$val, true);
	       if($counter < sizeof($hash)-1){
		    $this->nextObjectElement();
	       }
	       $counter++;
	  }
	  $this->endRootElement($this->documentRoot->getRootname());
     }

/**
 * It will detect what kind of element the element is and will print it accordingly. If it contains more elements it will print more recursively
 */
     private function printElement($key,$val, $root = false){
	  if(is_array($val)){
	       if(sizeof($val)>0){
		    $this->startArray($key,sizeof($val), $root);
		    foreach($val as $elementval){
			 $this->printElement($key,$elementval);
			 if($val[sizeof($val)-1] != $elementval){
			      $this->nextArrayElement();
			 }
		    }
	       }
	       $this->endArray($key, $root);
	  }else if(is_object($val)){
	       $this->startObject($key,$val);
	       $hash = get_object_vars($val);
	       $counter = 0;
	       foreach($hash as $elementkey => $elementval){
		    $this->printElement($elementkey,$elementval);
		    if($counter < sizeof($hash)-1){
			 $this->nextObjectElement();
		    }
		    $counter++;
	       }
	       $this->endObject($key);  
	  }else if(is_bool($val)){
	       $val = $val?1:0;//turn boolean into an int
	       $this->startKeyVal($key,$val);
	       $this->endElement($key);
	  }else if(!is_null($val)){
	       $this->startKeyVal($key,$val);
	       $this->endElement($key);
	  }else{
	       throw new Exception("Could not retrieve the right information - please report this problem to list@iRail.be or try again with other arguments.",500);
	  }
     }
     function nextArrayElement(){
     }
     function nextObjectElement(){
     }
     abstract function startRootElement($name, $version, $timestamp);

     abstract function startArray($name,$number, $root = false);
     abstract function startObject($name, $object);
     abstract function startKeyVal($key,$val);

     abstract function endArray($name, $root = false);
     function endObject($name){
	  $this->endElement($name);
     }
     abstract function endElement($name);
     abstract function endRootElement($name);

     abstract function printError($ec,$msg);     
  }

?>