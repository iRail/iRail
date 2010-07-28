<?php
/*	Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others

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

include 'includes/getVersion.php';
?>
<p style="color:#999999;font-size:12px;position:absolute;left:10px;">made by <a href="http://yeri.be/irail/" target="_blank">Yeri Tiete</a>; 
data from <a href="http://www.b-rail.be/" target="_blank">(B)</a>; 
version: <a href="changelog">
<?php 
$irailVersion = str_replace("\n", "", $irailVersion);
echo $irailVersion; 
?></a>.</p>

<?php
include 'includes/googleAnalytics.php';
?>