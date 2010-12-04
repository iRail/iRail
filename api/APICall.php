<?php
/*
 * This is a strange factory pattern:
 *  * It will create instances of the right objects to echo things to the client
 *
 * It is the error handler as well. If any error occurs, this knows what errorcode it should return.
 *
 * AT THIS MOMENT THIS CLASS HAS NO REAL USAGE. DON'T LOOK HERE IF YOU'RE HACKING SOMETHING IN
 */

/**
 * Description of APICall
 *
 * @author pieterc
 */
include_once("../includes/apiLog.php");
include_once("ErrorHandlers/ErrorHandler.php");
class APICall {

    protected $request;
    private $input;
    protected $datastruct;
    private $output;

    private $functionname;

    //Hooks
    private $CORS = true; // Cross Origin Resource Sharing
                         //Should be turned off for information when logged in
    private $LOGGING = true;
    private $error;

    function __construct($functionname, $request) {
        $this -> functionname = $functionname;
        $this -> request = $request;
        $this -> error = false;
        $this -> input = $request -> getInput();
    }

    public function executeCall(){
        $this -> addHeaders();
        try{
            $this -> datastruct =  $this -> input-> execute($this->request);
            $this -> output = $this -> request -> getOutput($this -> datastruct);
            $this ->printOutput();
            $this-> logRequest();
        }catch(Exception $e){
            $this->processError($e);
        }
    }

    public function disableCors(){
        $this->CORS = false;
    }

    public function disableLogging(){
        $this->LOGGING = false;
    }

    private function addHeaders(){
        if($this->CORS){
            header("Access-Control-Allow-Origin: *");
        }
    }

    protected function processError(Exception $e){
        writeLog($_SERVER['HTTP_USER_AGENT'],"", "", "Error in $this->functionname " . $e -> getMessage(), $_SERVER['REMOTE_ADDR']);
        $eh = new ErrorHandler($e, $this->request->getFormat());
        $eh -> printError();
    }

    protected function printOutput(){
        $this->output -> printAll();
    }

    //to be overriden
    protected function logRequest(){
        if($this->LOGGING){
            writeLog($_SERVER['HTTP_USER_AGENT'],"","", "none ($this->functionname)", $_SERVER['REMOTE_ADDR']);
        }
    }
}
?>
