<?php
  /** Copyright (C) 2011 by iRail vzw/asbl
   * This is a class with static tools for you to use on the NMBS scraper. It contains stuff that is needed by all other classes
   *
   * @package data/NMBS
   */

class tools{
     /**
      *
      * @param <type> $time -> in 00d15:24:00
      * @param <type> $date -> in 20100915
      * @return seconds since the Unix epoch
      *
      */
     public static function transformTime($time, $date) {
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
     public static function transformDuration($time) {
	  $days = intval(substr($time, 0,2));
	  $hour = intval(substr($time, 3, 2));
	  $minute = intval(substr($time, 6,2));
	  $second = intval(substr($time, 9,2));
	  return $days*24*3600 + $hour*3600 + $minute * 60 + $second;
     }


    /**
     * Adds a quarter and responds with a time.
     *
     * @param $time
     * @return string
     */
     public static function addQuarter($time){
	  preg_match("/(..):(..)/",$time, $m);
	  $hours = $m[1];
	  $minutes = $m[2];
	  //echo $hours . " " . $minutes . "\n";
	  if($minutes >= 45){
	       $minutes = ($minutes + 15)-60;
	       if($minutes < 10){
		    $minutes = "0" . $minutes;
	       }
	       $hours ++;
	       if($hours > 23){
		    $hours = "00";//no fallback for days?
	       }
	  }else{
	       $minutes +=15;
	  }
	  return $hours. ":" . $minutes;	  
     } 
  }