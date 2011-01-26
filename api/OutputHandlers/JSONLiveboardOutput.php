<?php

/**
 * Description of JSONLiveboardOutput
 *
 * @author Pieter Colpaert
 */
include_once("LiveboardOutput.php");

class JSONLiveboardOutput extends LiveboardOutput {

    private $liveboard;

    function __construct($lang, $l) {
        parent::__construct($lang);
        $this->liveboard = $l;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
        $xml = parent::buildXML($this->liveboard);

        $jsonstring = json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
        $jsonstring = preg_replace('/"@attributes":{(.*?)}/sm ', "\\1", $jsonstring);
	//This is the output of the json string.
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? ')' : '');


    }

    public function printJSON($liveboards){


	 return $liveboardJSON;	 
    }
    
}
?>
