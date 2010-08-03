<?php
/* Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
 	
 	This file is part of iRail.

    iRail is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    iRail is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with iRail.  If not, see <http://www.gnu.org/licenses/>.

	http://blog.irail.be - http://irail.be
	
	source available at http://github.com/Tuinslak/iRail
*/

// set maintenance mode
// 0 = forward to national page
// other = output msg 
$maintenance = 0;

// set dev mode on/off
// if dev is false, redirect to http://irail.be 
$notIrailDotBe = 1;

// get current domain
$domain = str_replace("www.","",$_SERVER['HTTP_HOST']);

// redirect to irail site instead of dev.irail.be
if($notIrailDotBe == 0 && $domain != "irail.be" && $domain != "irail.nl") {
	header('Location: http://irail.be');
	return;
}

// redirect to main page (nat); else display error message 
if($maintenance == 0) {
	// edit URL to match domain/site
	header('Location: national');
}else{
	echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
	return;
}

?>
