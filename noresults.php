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

$lang = $_COOKIE["language"];

switch($lang) {
    case "EN":      $txt_nok = "No reply. Most likely an invalid station name.";
        $txt_msg = 'An error occured';
        break;
    case "NL":      $txt_nok = "Geen antwoord. Gebruikte u een geldige stationsnaam?";
        $txt_msg = 'Er is een fout opgetreden bij het scrapen.';
        break;
    case "FR":      $txt_nok = "Pas de r&#233;ponse. Probablement un nom de gare incorrect.";
        $txt_msg = 'Erreur';
        break;
    case "DE":      $txt_nok = "Keine Antwort. Wahrscheinlich falscher Stationname.";
        $txt_msg = 'An error occured';
        break;
    default:        $txt_nok = "No reply. Most likely an invalid station name.";
        $txt_msg = 'An error occured';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>iRail - Error</title>
	<link href="/css/mobile.css" rel="stylesheet">
	<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
	<script>
		addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
		function hideAddressBar() { window.scrollTo(0, 1); }
	</script>
</head>

<body>
<div class="container">
<div class="toolbar anchorTop">
	<div class="title"><a href="..">Error</a> </div>
	<div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="/img/i.png" alt="Settings"></a></div>
<br>
  <div class="toolbar">  
	<div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
	<?php echo $txt_nok; ?>
	</div>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="settings" method="post" action="..">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
<tr><td colspan="3" style="font-size:small;"><br> <?php echo $txt_msg; ?> <br> </td></tr>
<tr>
<td></td>
<td><div style="text-align:center;"><input type="submit" name="submit" value="Back"></div></td>
<td></td>
</tr>
</table>
</td>
</form>
</tr></table>
<?php
include 'includes/footer.php';
?>
</div></div></div>
</body>
</html>