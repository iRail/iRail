<?php
/* 	Copyright 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
	
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

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>iRail - About</title>
  <link href="css/mobile.css" rel="stylesheet">
  <link rel="apple-touch-icon" href="./img/irail.png">
  <link rel="shortcut icon" href="./img/favicon.ico">
  <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
  <script>
  	addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
  	function hideAddressBar() { window.scrollTo(0, 1); }
  </script>
</head>

<body>
<div class="container">
<div class="toolbar anchorTop">
	<div class="title"><a href="..">About</a> </div>
	<div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="./img/i.png" alt="Settings"></a></div>
<br>
  <div class="toolbar">  
	<div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
	iRail project.
	</div>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
<tr>
<td width="100">

<center>

<p id="about">Project iRail (<a href="http://project.irail.be" target="_blank">project.irail.be<a/>) </p>

<table bgcolor="#FFFFFF" style="color:#000000";>
<tr><td>


<li id="about_s"> API (<a href="http://api.irail.be" target="_blank">api.irail.be</a>) </li>
<li id="about_s"> Created by: </li>
<li id="about_vs">Yeri Tiete (<a href="http://yeri.be/" target="_blank">Tuinslak</a>),</li> 
<li id="about_vs"><a href="http://bonsansnom.wordpress.com/" target="_blank">Pieter Colpaert</a>,</li> 
<li id="about_vs">Christophe Versieux,</li> 
<li id="about_vs">and <a href="http://project.irail.be/cgi-bin/trac.fcgi/wiki/Contributors" target="_blank">many others</a>.</li>


</td>
</tr>
</table>


</center> 

</td>
</tr>
<tr><td colspan="3"><br></td></tr>
<tr>
<td colspan="3"><div style="text-align:center;">

<form>
<input type="button" value="Back" onClick="location.href='..'">
</form>

</div></td>
</tr>
</table>
</td>
</form>
</tr></table>
<?php
include 'footer.php';
?>
</div></div></div>
</body>
</html>