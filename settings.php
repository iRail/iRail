<?php
/*
 * iRail by Tuinslak
 * http://yeri.be / http://irail.be
 * WARNING: read DISCLAIMER
 *
 */

// Settings page

$lang = "";

$lang = $_COOKIE["language"];

switch($lang) {
case "EN":		$txt_lang = "Language:";
                break;
case "NL":		$txt_lang = "Taal:";
                break;
case "FR":      $txt_lang = "Langue:";
                break;
case "DE":      $txt_lang = "Sprache:";
                break;
default:		$txt_lang = "Language:";
                break;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link href="css/mobile.css" rel="stylesheet" type="text/css" />
<link rel="apple-touch-icon" href="./img/irail.png" />
<link rel="shortcut icon" type="image/x-icon" href="./img/favicon.ico">
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<title>
iRail - Settings
</title>
<script type="application/x-javascript">
	addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
	function hideAddressBar() { window.scrollTo(0, 1); }
</script>
</head>

<body>
<div class="container">
<div class="toolbar anchorTop">
	<div class="title"><a href="..">Settings</a> </div>
	<div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="./img/i.png" alt="Settings" /></a></div>
<br />
  <div class="toolbar">  
	<div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
	Saved in a cookie! Yum.
	</div>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="settings" method="post" action="save">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
<tr>
<td width="100"><?php echo $txt_lang; ?></td>
<td colspan="2"><SELECT NAME="lang">
<OPTION VALUE="EN" <?php if($lang == "EN") { echo "SELECTED"; } ?> >English</OPTION>
<OPTION VALUE="NL" <?php if($lang == "NL") { echo "SELECTED"; } ?> >Nederlands</OPTION>
<OPTION VALUE="FR" <?php if($lang == "FR") { echo "SELECTED"; } ?> >Fran&#231;ais</OPTION>
<OPTION VALUE="DE" <?php if($lang == "DE") { echo "SELECTED"; } ?> >Deutsch</OPTION>
</SELECT></td>
</tr>
<tr><td colspan="3"><br /></td></tr>
<tr>
<td colspan="3"><div style="text-align:center;"><input type="submit" name="submit" value="Save!"></div></td>
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