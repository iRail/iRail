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

if($maintenance == 0) {
	header('Location: http://irail.be/national');
}else{
	echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
}

?>