<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */
 
// sets cookie 

if($lang == "EN" || $lang == "FR" || $lang == "NL" || $lang == "DE") {
	setcookie("language", $lang, time()+60*60*24*360);
	header('Location: ..');
}else{
	header('Location: ./settings');
}
?>
