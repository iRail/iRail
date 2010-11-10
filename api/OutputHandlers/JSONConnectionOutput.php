<?php

/**
 * Description of JSONConnectionOutput
 *
 * @author pieterc
 */

include_once("ConnectionOutput.php");
class JSONConnectionOutput extends ConnectionOutput {
    private $connections;

    function __construct($c) {
        $this -> connections = $c;
    }

    public function printAll() {
        date_default_timezone_set("UTC");
        header("Content-Type: application/json;charset=UTF-8");
        $xml = parent::buildXML($this->connections);
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        //yes this may cause some overhead, but it's the easiest way to implement this for now.
        $jsonstring = json_encode(new SimpleXMLElement($xml->saveXML(), LIBXML_NOCDATA));
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? $callback . '(' : '');
    }
}
?>
