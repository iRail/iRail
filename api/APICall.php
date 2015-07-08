<?php
  /* Copyright (C) 2011 by iRail vzw/asbl
   *
   * This class foresees in basic REST functionality. It will get all the GET vars and put it in a request. This requestobject will be given as a parameter to the DataRoot object, which will fetch the data and will give us a printer to print the header and body of the HTTP response.
   *
   * @author Pieter Colpaert
   */

use Dotenv\Dotenv;

ini_set("include_path", ".:data");
include_once("data/DataRoot.php");
include_once("data/structs.php");
class APICall {

     private $VERSION = 1.1;

     protected $request;
     protected $dataRoot;

     //Hooks
     private $logging = true;

     function __construct($functionname) {
	  try{
	       $requestname = ucfirst(strtolower($functionname)) . "Request";
	       include_once("requests/$requestname.php");
	       $this->request = new $requestname();
	       $this->dataRoot = new DataRoot($functionname, $this->VERSION, $this->request->getFormat());
           $dotenv = new Dotenv(dirname(__DIR__));
           $dotenv->load();
	  }catch(Exception $e){
	       $this->buildError($functionname, $e);
	  }
     }

     private function buildError($fn, $e){
	  $this->logError($fn, $e);
//get the right format - This is some duplicated code and I hate to write it
	  $format = "";
	  if(isset($_GET["format"])){
	       $format = $_GET["format"];
	  }
	  if($format == ""){
	       $format = "Xml";
	  }
	  $format = ucfirst(strtolower($format));
	  if(isset($_GET["callback"]) && $format=="Json"){
	       $format = "Jsonp";
	  }
	  if(!file_exists("output/$format.php")){
	       $format="Xml";
	  }
	  include_once("output/$format.php");
	  $printer = new $format(NULL);
	  $printer->printError($e->getCode(),$e->getMessage());
	  exit(0);
     }

     public function executeCall(){
	  try{
	       $this->dataRoot->fetchData($this->request, $this->request->getSystem());
	       $this->dataRoot->printAll();
	       $this->logRequest();
	  } catch(Exception $e){
	       $this->buildError($this->dataRoot->getRootname(), $e);
	  }
     }

     public function disableLogging(){
	  $this->logging = false;
     }

     protected function logError($functionname, Exception $e){
	  if(!isset($_SERVER['HTTP_USER_AGENT']))
                $_SERVER['HTTP_USER_AGENT'] = "unknown";
	  if($this->logging){
	       $this->writeLog($_SERVER['HTTP_USER_AGENT'],"", "", "Error in $functionname " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
	  }
     }

     //to be overriden
     protected function logRequest(){
	if(!isset($_SERVER['HTTP_USER_AGENT']))
		$_SERVER['HTTP_USER_AGENT'] = "unknown";
	  if($this->logging){
	       $functionname = $this->dataRoot->getRootname();
	       $this->writeLog($_SERVER['HTTP_USER_AGENT'],"","", "none ($functionname)", $_SERVER['REMOTE_ADDR']);
	  }
     }

     protected function writeLog($ua, $from, $to, $err, $ip) {
	  // get time + date in rfc2822 format
	  date_default_timezone_set('Europe/Brussels');
	  $now = date('D, d M Y H:i:s');
	  if($from == "") {
	       $from = "EMPTY";
	  }
	  if($to == "") {
	       $to = "EMPTY";
	  }
	  if ($ua == "") {
	       $ua = "-";
	  }
	  APICall::connectToDB();
	  $from = mysqli_real_escape_string($from);
	  $to = mysqli_real_escape_string($to);
	  $err = mysqli_real_escape_string($err);
	  $ip = mysqli_real_escape_string($ip);
	  $ua = mysqli_real_escape_string($ua);
	  // insert in db
	  try {
          // Loaded by dotenv
          $selectColumns = [
              $_ENV['column1'], $_ENV['column2'], $_ENV['column3'], $_ENV['column4'],
              $_ENV['column5'], $_ENV['column6'], $_ENV['column7'], $_ENV['column8']
          ];

	       $query = "INSERT INTO ". $_ENV['apiTable'] ." ($selectColumns)
	                    VALUES('$now', '$ua', '$from', '$to', '$err', '$ip', '$api_server_name')";

	       $result = mysqli_query($query);
	  }
	  catch (Exception $e) {
	       echo "Error writing to the database.";
	  }
     }


     public static function connectToDB(){
	  try {
          $connectionInfo = [$_ENV['apiHost'], $_ENV['apiUser'], $_ENV['apiPassword']];

	       mysqli_connect($connectionInfo);
	       mysqli_select_db($_ENV['apiDatabase']);
	  }
	  catch (Exception $e) {
	       throw new Exception("Error connecting to the database.", 3);
	  }
     }
}
