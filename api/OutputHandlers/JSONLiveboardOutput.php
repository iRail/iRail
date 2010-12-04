<?php

/**
 * Description of JSONLiveboardOutput
 *
 * @author pieterc
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
        //yes this may cause some overhead, but it's the easiest way to implement this for now.
        $jsonstring = json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
        $jsonstring = preg_replace('/"@attributes":{(.*?)}/sm ', "\\1", $jsonstring);
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? ')' : '');
    }

}
?>
