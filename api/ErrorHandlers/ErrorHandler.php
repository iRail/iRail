<?php
/**
 * Description of ErrorHandler
 *
 * @author pieterc
 */
include_once("ErrorHandlers/JSONError.php");
include_once("ErrorHandlers/XMLError.php");
class ErrorHandler {
    private $exception;
    private $errorHandler;

    function __construct($exception, $format) {
        $this->exception = $exception;
        if(strtolower($format) == "json"){
            $this->errorHandler = new JSONError();
        }else if(strtolower($format) == "xml"){
            $this->errorHandler = new XMLError();
        }
    }

    
    public function printError(){
        $this->errorHandler -> printError($this->exception);
    }
}
?>
