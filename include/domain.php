<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */

// get domain (dev or not ?)
// edit to use with your own domain
// make sure .htaccess is correctly set up

$domain = str_replace("www.","",$_SERVER['HTTP_HOST']);

if($domain == "irail.be" || $domain == "irail.nl") {
	$national_page = "national";
	$international_page = "international";
	$query_page = "results";
	$int_query_page = "iresults";
	$settings_page = "settings";
	
}else{
	$national_page = "national.php";
	$international_page = "international.php";
	$query_page = "query.php";
	$int_query_page = "iquery.php";
	$settings_page = "settings.php";
}

?>