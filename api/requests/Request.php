<?php

  /* Copyright (C) 2011 by iRail vzw/asbl 
   *
   * This is an interface to a Request
   *
   * @author pieterc
   */
class Request {
     public static $SUPPORTED_LANGUAGES = array("EN", "NL", "FR", "DE");
     public static $SUPPORTED_SYSTEMS = array("NMBS", "MIVB", "NS", "DL");

     private $format = "xml";
     private $lang = "EN";
     private $system = "NMBS";

     protected function processRequiredVars($array){
	  foreach($array as $var){
	       if(!isset($this->$var) || $this->$var == "" || is_null($this->$var)){	    
		    throw new Exception("$var not set. Please review your request and add the right parameters",400);
	       }
	  }
     }

    /**
     * will take a get a variable from the GET array and set it as a member variable
     *
     * @param $varName
     * @param $default
     */
     protected function setGetVar($varName, $default){
	  if(isset($_GET[$varName])){
	       $this->$varName = $_GET[$varName];
	  }else{
	       $this->$varName = $default;
	  }
     }
     

     function __construct() {
	  $this->setGetVar("format", "xml");
	  $this->setGetVar("lang", "EN"); 
	  $this->setGetVar("system", "NMBS");
     }

    /**
     * @return string
     */
    public function getFormat() {
	  return strtolower($this->format);
     }

    /**
     * @return string
     */
    public function getLang() {
	  $this->lang = strtoupper($this->lang);
	  if(in_array($this->lang, Request::$SUPPORTED_LANGUAGES)){
	       return $this->lang;
	  }
	  return "EN";
     }

    /**
     * @return string
     */
    public function getSystem(){
	  $this->system = strtoupper($this->system);	  
	  if(in_array($this->system, Request::$SUPPORTED_SYSTEMS)){
	       return $this->system;
	  }else{
	       return "NMBS";
	  }
     }
     
  }
?>
