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

// set dev mode on/off
// if dev is false, redirect to http://irail.be 
$dev = 0;

// get current domain
$domain = str_replace("www.","",$_SERVER['HTTP_HOST']);

echo $domain;

if($dev == 0 && $domain != "irail.be" && $domain != "irail.nl") {
	header('Location: http://irail.be');
}

if($maintenance == 0) {
	// edit URL to match domain/site
	header('Location: national');
}else{
	echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
	return;
}

?>