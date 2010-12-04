<?php
/**
 * Description of XMLError
 *
 * @author pieterc
 */
class XMLError {
    public function printError(Exception $e){
        header('Content-Type: text/xml');
        $msg = $e->getMessage();
        $errorCode = $e ->getCode();
        $xml = new DOMDocument("1.0", "UTF-8");
        $rootNode = $xml->createElement("error", $msg);
        $rootNode->setAttribute("version", "1.0");
        $rootNode->setAttribute("timestamp", date("U"));
        $rootNode -> setAttribute("code", $errorCode);
        $xml->appendChild($rootNode);
        echo $xml->saveXML();
    }
}
?>
