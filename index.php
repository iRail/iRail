<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */

// set maintenance mode
// 0 = forward to national page
// other = output msg 
$maintenance = 0;

// get domain (dev or not ?)
$domain = str_replace("www.","",$_SERVER['HTTP_HOST']);

if($domain == "irail.be" || $domain == "irail.nl") {
	if($maintenance == 0) {
		// edit URL to match domain/site
		header('Location: http://irail.be/national');
	}else{
		echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
	}
}else{
	header('Location: national.php');
}

?>