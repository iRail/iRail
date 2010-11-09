<?php
/**
 *
 * @author pieterc
 */
interface Output {
    //put your code here
    public function printAll();
    public function printError($errorCode, $msg);
}
?>
