<?php
$lang = $_COOKIE["language"];

switch($lang) {
case "EN":      $txt_nok = "No reply. Most likely an invalid station name.";
		$txt_msg = 'An error occured because the NMBS/SNCB site did not provide the expected information. <br />
			Make sure you gave the full station name (e.g. Brussels Central; including Central). <br /><br />
			You should also know that the NMBS/SNCB website only supoorts queries in the recent future or past.<br /> 
			So make sure you picked a recent date.';
                break;
case "NL":      $txt_nok = "Geen antwoord. Gebruikte u een geldige stationsnaam?";
                $txt_msg = 'Er is een fout opgetreden omdat de NMBS website niet het verwachte antwoord gaf. <br />
			U dient er zeker van te zijn dat u de volledige stationsnaam gebruikt (b.v. Brussel Centraal; inclusief Centraal). <br /><br />
			U dient er ook bewust van te zijn dat de NMBS website alleen data in een nabije toekomst of verleden ondersteunt.<br />
			Gebruik dus recente data.';
		break;
case "FR":      $txt_nok = "Pas de r&#233;ponse. Probablement un nom de gare incorrect.";
                $txt_msg = 'Le site de la SNCB n\'a pas envoy&#233; la r&#233;ponse attendue. <br />
			Introduisez le nom complet de la gare (p.e. Bruxelles Central, Bruxelles-Midi, et non Bruxelles tout court)<br /><br />
			Le site de la SNCB accepte aussi bien les dates &#224; venir que les dates futures, &#224; condition qu\'elles sont proches dans le temps.';
		break;
case "DE":      $txt_nok = "Keine Antwort. Wahrscheinlich falscher Stationname.";
                $txt_msg = 'An error occured because the NMBS/SNCB site did not provide the expected information. <br />
                        Make sure you gave the full station name (e.g. Brussels Central; including Central). <br /><br />
                        You should also know that the NMBS/SNCB website only supoorts queries in the recent future or past.<br />
                        So make sure you picked a recent date.';
		break;
default:        $txt_nok = "No reply. Most likely an invalid station name.";
                $txt_msg = 'An error occured because the NMBS/SNCB site did not provide the expected information. <br />
                        Make sure you gave the full station name (e.g. Brussels Central; including Central). <br /><br />
                        You should also know that the NMBS/SNCB website only supoorts queries in the recent future or past.<br /> 
                        So make sure you picked a recent date.';
		break;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link rel="apple-touch-icon" href="./img/irail.png" />
<link href="mobile.css" rel="stylesheet" type="text/css" />
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<title>
iRail - Error
</title>
<script type="application/x-javascript">
	addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
	function hideAddressBar() { window.scrollTo(0, 1); }
</script>
</head>

<body>
<div class="container">
<div class="toolbar anchorTop">
	<div class="title"><a href="..">Error</a> </div>
	<div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="./img/i.png" alt="Settings" /></a></div>
<br />
  <div class="toolbar">  
	<div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
	<?php echo $txt_nok; ?>
	</div>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="settings" method="post" action="..">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
<tr><td colspan="3" style="font-size:small;"><br /> <?php echo $txt_msg; ?> <br /> </td></tr>
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
include 'footer.php';
?>
</div></div></div>

<?php
include 'ga.inc.php';
?>

</body>
</html>
