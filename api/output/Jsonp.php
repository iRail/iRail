<?php
  /* Copyright (C) 2011 by iRail vzw/asbl */
include_once("Json.php");

/**
 * Prints the Jsonp style output
 *
 * @package output
 */
class Jsonp extends Json
{
     function printHeader(){
	  header("Access-Control-Allow-Origin: *");
	  header("Content-Type: application/javascript;charset=UTF-8"); 
     }

    function printBody(){
	  $callback = $_GET['callback'];
	  echo "$callback(";
	  parent::printBody($this->documentRoot);
	  echo ")";
     }

    /**
     * @param $ec
     * @param $msg
     */
    function printError($ec, $msg){
	  $this->printHeader();
	  header("HTTP/1.1 $ec $msg");
	  echo $_GET['callback'] . "({\"error\":$ec, \"message\": \"$msg\"})";
     }

};