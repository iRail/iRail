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
class APICall {

    private $request;
    private $input;
    private $output;

    private $error;

    function __construct($lang, $request) {
        $this ->error = false;
        $this ->input = $request -> getInput();
    }

    public function executeCall(){
        
        if($error){
            $this->printError();
        }else{
            $this ->printOuput();
        }
    }

    private function printError(){

    }

    private function printOuput(){
        $output -> printAll();
    }
}
?>
