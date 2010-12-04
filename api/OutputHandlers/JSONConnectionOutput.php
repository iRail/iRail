<?php

/**
 * Description of JSONConnectionOutput
 *
 * @author pieterc
 */

include_once("ConnectionOutput.php");
class JSONConnectionOutput extends ConnectionOutput {
    private $connections;

    function __construct($lang, $c) {
        parent::__construct($lang);
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
        $xml = parent::buildXML($this->connections);
        //yes this may cause some overhead, but it's the easiest way to implement this for now.
        $jsonstring = json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
        $jsonstring = preg_replace('/"@attributes":{(.*?)}/sm ', "\\1", $jsonstring);
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? ')' : '');
    }
}
?>
