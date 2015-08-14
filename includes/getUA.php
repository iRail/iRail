<?php
/* 	Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
	
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

	http://project.irail.be - http://irail.be
	
	source available at http://github.com/Tuinslak/iRail
*/
include 'getVersion.php';
// add trailing "-" to prevent RH/Apache/NMBS error: HTTP header: invalid date
$irailAgent = "iRail.be by Open Knowledge Belgium (https://hello.irail.be); Git version: $irailVersion -";

// result example:
// 85.12.6.130 - - [20/Sep/2010:14:57:58 +0200] "POST / HTTP/1.1" 200 119564 "http://irail.be/" "iRail.be by Project iRail (http://yeri.be/dx); Git version: 902542a"
