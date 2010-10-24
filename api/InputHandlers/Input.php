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

    /**
     *
     * @param <type> $time -> in 00d15:24:00
     * @param <type> $date -> in 20100915
     * @return seconds since the Unix epoch
     *
     */
    protected static function transformTime($time, $date) {
        //I solved it with substrings. DateTime class is such a Pain In The Ass.
        date_default_timezone_set("Europe/Brussels");
        $dayoffset = intval(substr($time,0,2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        $year = intval(substr($date, 0,4));
        $month = intval(substr($date, 4,2));
        $day = intval(substr($date,6,2));
        return mktime($hour, $minute, $second, $month, $day + $dayoffset, $year);
    }
    /**
     * This function transforms the brail formatted timestring and reformats it to seconds
     * @param int $time
     * @return int Duration in seconds
     */
    protected static function transformDuration($time) {
        $days = intval(substr($time, 0,2));
        $hour = intval(substr($time, 3, 2));
        $minute = intval(substr($time, 6,2));
        $second = intval(substr($time, 9,2));
        return $days*24*3600 + $hour*3600 + $minute * 60 + $second;
    }
}
?>
