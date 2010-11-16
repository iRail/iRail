<?php

/**
 * Description of JSONError
 *
 * @author pieterc
 */
class JSONError {

    public function printError(Exception $e) {
        $callback = isset($_GET['callback']) && ctype_alnum($_GET['callback']) ? $_GET['callback'] : false;
        extract($_GET);
        header('Content-Type: ' . ($callback ? 'application/javascript' : 'application/json') . ';charset=UTF-8');
        $jsonstring = "{\"error\":" . $e->getCode() . ", \"message\": \"" . $e->getMessage() . "\"}";
        echo ($callback ? $callback . '(' : '') . $jsonstring . ($callback ? ')' : '');
    }

}
?>
