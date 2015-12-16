<?php
/* Copyright (C) 2011 by iRail vzw/asbl */
/**
* This is the root of every document. It will specify a version and timestamp. It also has the printer class to print the entire document.
*
* @package data
*/
class DataRoot
{
  private $printer;
  private $rootname;

  public $version;
  public $timestamp;

  /**
  * constructor of this class
  *
  * @param $rootname
  * @param double $version the version of the API
  * @param $format
  * @param string $error
  * @throws Exception
  * @internal param format $string the format of the document: json, json or XML
  */
  function __construct($rootname, $version, $format, $error = "") {
    //We're making this in the class form: Json or Xml or Jsonp
    $format = ucfirst(strtolower($format));
    //fallback for when callback is set but not the format= Jsonp
    if(isset($_GET["callback"]) && $format=="Json"){
      $format = "Jsonp";
    }
    if(!file_exists("output/$format.php")){
      throw new Exception("Incorrect format specified. Please correct this and try again",402);
    }
    include_once("output/$format.php");
    $this->printer = new $format($this);
    $this->version = $version;
    $this->timestamp = date("U");
    $this->rootname = $rootname;
  }

  /**
  * @return mixed
  */
  public function getPrinter(){
    return $printer;
  }

  /**
  * Print everything
  */
  public function printAll(){
    $this->printer->printAll();
  }

  /**
  * @return mixed
  */
  public function getRootname(){
    return $this->rootname;
  }

  /**
  * @param $request
  * @param $SYSTEM
  * @throws Exception
  */
  public function fetchData($request, $SYSTEM){
    try{
      include_once("data/$SYSTEM/$this->rootname.php");
      $rn = $this->rootname;
      $rn::fillDataRoot($this,$request);
    }catch(Exception $e){
      if ($e->getCode() == '404') {
        throw new Exception($e->getMessage(), 404);
      } else if ($e->getCode() == '300') {
        throw new Exception($e->getMessage(), 300);
      } else {
        throw new Exception("Could not get data. Please report this problem to iRail@list.iRail.be.", 502);
      }
    }

  }

}
