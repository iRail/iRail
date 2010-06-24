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

// get domain + vars
include 'include/domain.php';

if($maintenance == 0) {
	// edit URL to match domain/site
	header('Location: $national');
}else{
	echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
}

?>