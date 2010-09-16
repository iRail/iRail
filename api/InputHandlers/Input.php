<?php
/**
 * Template design pattern
 *
 * This is the interface that will get the data from a server and unmarshall it.
 *
 * @author pieterc
 */
abstract class Input {
    protected abstract function fetchData(Request $request);
    protected abstract function transformData($serverData);
    public function execute(Request $request){
        $serverData = $this->fetchData($request);
        return $this->transformData($serverData);
    }
}
?>
