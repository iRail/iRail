<?php
$from = $_COOKIE["from"];
$to = $_COOKIE["to"];
$lang = $_COOKIE["language"];

// SPECIAL MSG?
// 1 or 0
$special = 0;

switch($lang) {
case "EN":    $txt_from = "From:";
                $txt_to = "To:";
                $txt_date = "Date:";
                $txt_time = "Time:";
        $txt_arrive = "Arrival";
        $txt_depart = "Departure";
        $txt_special = "<img src='hafas/img/icon_warning.gif' alt='(!)' /> 6 Oct: possible strikes at B-rail/MIVB/De Lijn";
                break;
case "NL":    $txt_from = "Van:";
                $txt_to = "Naar:";
                $txt_date = "Datum:";
                $txt_time = "Tijd:";
                $txt_arrive = "Aankomst";
                $txt_depart = "Vertrek";
        $txt_special = "<img src='hafas/img/icon_warning.gif' alt='(!)' /> 6 Okt: Mogelijke stakingen bij NMBS/MIVB/De Lijn";
                break;
case "FR":      $txt_from = "De:";
                $txt_to = "Vers:";
                $txt_date = "Date:";
                $txt_time = "Heure:";
                $txt_arrive = "Arriv&#233;e";
                $txt_depart = "D&#233;part";
        $txt_special = "<img src='hafas/img/icon_warning.gif' alt='(!)' /> 6 Oct: Possibilit&#233; de gr&#232;ves &#224; SNCB/MIVB/De Lijn";
                break;
case "DE":      $txt_from = "Von:";
                $txt_to = "Nach:";
                $txt_date = "Datum:";
                $txt_time = "Uhrzeit:";
                $txt_arrive = "Ankunft";
                $txt_depart = "Abfahrt";
        $txt_special = "<img src='hafas/img/icon_warning.gif' alt='(!)' /> 6 Okt: M&#246;gliche Schl&#228;ge an B-rail/MIVB/De Lijn";
                break;
default:    $txt_from = "From:";
                $txt_to = "To:";
                $txt_date = "Date:";
                $txt_time = "Time:";
                $txt_arrive = "Arrival";
                $txt_depart = "Departure";
        $txt_special = "<img src='hafas/img/icon_warning.gif' alt='(!)' /> 6 Oct: possible strikes at B-rail/MIVB/De Lijn";
                break;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link href="mobile.css" rel="stylesheet" type="text/css" />
<link rel="apple-touch-icon" href="./img/irail.png" />
<meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<meta name="keywords" content="nmbs, sncb, iphone, mobile, irail, irail.be, route planner">
<meta name="language" content="en"> 
<META NAME="DESCRIPTION" CONTENT="NMBS/SNCB iPhone train route planner.">
<meta name="verify-v1" content="CKTzWOdgOxi/n81oG7ycuF/h8UKhX9OAhfmOA0nQ+Ts=" />
<title>
iRail
</title>

<script language="javascript" type="application/x-javascript"> function switch_station() {
      var tmp = "";
      tmp = document.getElementById("from").value;
      document.getElementById("from").value = document.getElementById("to").value;
        document.getElementById("to").value = tmp;
}</script>

<script type="application/x-javascript">
    addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
    function hideAddressBar() { window.scrollTo(0, 1); }
</script>
</head>

<body>
<div class="container">
<div class="toolbar anchorTop">
    <div class="title"><a href="..">iRail</a> </div>
    <div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="./img/i.png" alt="Settings" /></a></div>
<br />
  <div class="toolbar">  
    <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
    <?php echo date('l j/m/Y - H:i'); ?> 
<!--    /<span style="color:red;font-weight:bold;">/ NMBS site has probs.</span> -->
    </div>

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr>
<form name="search" method="post" action="results">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
<?php
if($special == 1) {
?>
    <tr><td colspan="3" style="text-align:center;font-weight:normal;color:red;font-size:14px;"><?php echo $txt_special; ?></td>
<?php
}
?>
<tr>
<td width="70"><?php echo $txt_from; ?></td>
<td colspan="2"><input name="from" type="text" id="from" AUTOCOMPLETE="OFF" value="<?php echo $from; ?>">
<script language="javascript" type="text/javascript"> function reset_from() {
        document.getElementById("from").value = "";
}</script>
<a href="#" onclick="javascript:reset_from()"><img src="img/x.png" alt="X" border="0" /></a>
</td>
</tr>
<tr>
<td><?php echo $txt_to; ?></td>
<td colspan="2"><input name="to" type="text" id="to" AUTOCOMPLETE="OFF" value="<?php echo $to; ?>">
<script language="javascript" type="text/javascript"> function reset_to() {
        document.getElementById("to").value = "";
}</script>
<a href="#" onclick="javascript:reset_to()"><img src="img/x.png" alt="X" border="0" />
</td>
</tr>
<tr><td colspan="3"><br /></td></tr>
<tr>
<td><?php echo $txt_date; ?></td>
<td colspan="2">
<SELECT NAME="d">
<OPTION VALUE="01" <?php if(date('d') == '01') { echo "SELECTED"; } ?> >01</OPTION> 
<OPTION VALUE="02" <?php if(date('d') == '02') { echo "SELECTED"; } ?> >02</OPTION>
<OPTION VALUE="03" <?php if(date('d') == '03') { echo "SELECTED"; } ?> >03</OPTION>
<OPTION VALUE="04" <?php if(date('d') == '04') { echo "SELECTED"; } ?> >04</OPTION>
<OPTION VALUE="05" <?php if(date('d') == '05') { echo "SELECTED"; } ?> >05</OPTION>
<OPTION VALUE="06" <?php if(date('d') == '06') { echo "SELECTED"; } ?> >06</OPTION>
<OPTION VALUE="07" <?php if(date('d') == '07') { echo "SELECTED"; } ?> >07</OPTION>
<OPTION VALUE="08" <?php if(date('d') == '08') { echo "SELECTED"; } ?> >08</OPTION>
<OPTION VALUE="09" <?php if(date('d') == '09') { echo "SELECTED"; } ?> >09</OPTION>
<OPTION VALUE="10" <?php if(date('d') == '10') { echo "SELECTED"; } ?> >10</OPTION>
<OPTION VALUE="11" <?php if(date('d') == '11') { echo "SELECTED"; } ?> >11</OPTION>
<OPTION VALUE="12" <?php if(date('d') == '12') { echo "SELECTED"; } ?> >12</OPTION>
<OPTION VALUE="13" <?php if(date('d') == '13') { echo "SELECTED"; } ?> >13</OPTION>
<OPTION VALUE="14" <?php if(date('d') == '14') { echo "SELECTED"; } ?> >14</OPTION>
<OPTION VALUE="15" <?php if(date('d') == '15') { echo "SELECTED"; } ?> >15</OPTION>
<OPTION VALUE="16" <?php if(date('d') == '16') { echo "SELECTED"; } ?> >16</OPTION>
<OPTION VALUE="17" <?php if(date('d') == '17') { echo "SELECTED"; } ?> >17</OPTION>
<OPTION VALUE="18" <?php if(date('d') == '18') { echo "SELECTED"; } ?> >18</OPTION>
<OPTION VALUE="19" <?php if(date('d') == '19') { echo "SELECTED"; } ?> >19</OPTION>
<OPTION VALUE="20" <?php if(date('d') == '20') { echo "SELECTED"; } ?> >20</OPTION>
<OPTION VALUE="21" <?php if(date('d') == '21') { echo "SELECTED"; } ?> >21</OPTION>
<OPTION VALUE="22" <?php if(date('d') == '22') { echo "SELECTED"; } ?> >22</OPTION>
<OPTION VALUE="23" <?php if(date('d') == '23') { echo "SELECTED"; } ?> >23</OPTION>
<OPTION VALUE="24" <?php if(date('d') == '24') { echo "SELECTED"; } ?> >24</OPTION>
<OPTION VALUE="25" <?php if(date('d') == '25') { echo "SELECTED"; } ?> >25</OPTION>
<OPTION VALUE="26" <?php if(date('d') == '26') { echo "SELECTED"; } ?> >26</OPTION>
<OPTION VALUE="27" <?php if(date('d') == '27') { echo "SELECTED"; } ?> >27</OPTION>
<OPTION VALUE="28" <?php if(date('d') == '28') { echo "SELECTED"; } ?> >28</OPTION>
<OPTION VALUE="29" <?php if(date('d') == '29') { echo "SELECTED"; } ?> >29</OPTION>
<OPTION VALUE="30" <?php if(date('d') == '30') { echo "SELECTED"; } ?> >30</OPTION>
<OPTION VALUE="31" <?php if(date('d') == '31') { echo "SELECTED"; } ?> >31</OPTION>
</select>/<SELECT NAME="mo">
<OPTION VALUE="01" <?php if(date('m') == '01') { echo "SELECTED"; } ?> >01</OPTION>
<OPTION VALUE="02" <?php if(date('m') == '02') { echo "SELECTED"; } ?> >02</OPTION>
<OPTION VALUE="03" <?php if(date('m') == '03') { echo "SELECTED"; } ?> >03</OPTION>
<OPTION VALUE="04" <?php if(date('m') == '04') { echo "SELECTED"; } ?> >04</OPTION>
<OPTION VALUE="05" <?php if(date('m') == '05') { echo "SELECTED"; } ?> >05</OPTION>
<OPTION VALUE="06" <?php if(date('m') == '06') { echo "SELECTED"; } ?> >06</OPTION>
<OPTION VALUE="07" <?php if(date('m') == '07') { echo "SELECTED"; } ?> >07</OPTION>
<OPTION VALUE="08" <?php if(date('m') == '08') { echo "SELECTED"; } ?> >08</OPTION>
<OPTION VALUE="09" <?php if(date('m') == '09') { echo "SELECTED"; } ?> >09</OPTION>
<OPTION VALUE="10" <?php if(date('m') == '10') { echo "SELECTED"; } ?> >10</OPTION>
<OPTION VALUE="11" <?php if(date('m') == '11') { echo "SELECTED"; } ?> >11</OPTION>
<OPTION VALUE="12" <?php if(date('m') == '12') { echo "SELECTED"; } ?> >12</OPTION>
</select>/<SELECT NAME="y">
<OPTION VALUE="08" <?php if(date('y') == '08') { echo "SELECTED"; } ?> >2008</OPTION>
<OPTION VALUE="09" <?php if(date('y') == '09') { echo "SELECTED"; } ?> >2009</OPTION>
</select></td>
</tr>
<tr>
<td><?php echo $txt_time; ?></td>
<td colspan="2">
<SELECT NAME="h">
<?php
if(date('i') >= '50' && date('i') <= '59') { 
    $hour = date('H') + 1; 
}else{
    $hour = date('H');
}
?>
<OPTION VALUE="00" <?php if($hour == '00') { echo "SELECTED"; } ?> >00</OPTION>
<OPTION VALUE="01" <?php if($hour == '01') { echo "SELECTED"; } ?> >01</OPTION>
<OPTION VALUE="02" <?php if($hour == '02') { echo "SELECTED"; } ?> >02</OPTION>
<OPTION VALUE="03" <?php if($hour == '03') { echo "SELECTED"; } ?> >03</OPTION>
<OPTION VALUE="04" <?php if($hour == '04') { echo "SELECTED"; } ?> >04</OPTION>
<OPTION VALUE="05" <?php if($hour == '05') { echo "SELECTED"; } ?> >05</OPTION>
<OPTION VALUE="06" <?php if($hour == '06') { echo "SELECTED"; } ?> >06</OPTION>
<OPTION VALUE="07" <?php if($hour == '07') { echo "SELECTED"; } ?> >07</OPTION>
<OPTION VALUE="08" <?php if($hour == '08') { echo "SELECTED"; } ?> >08</OPTION>
<OPTION VALUE="09" <?php if($hour == '09') { echo "SELECTED"; } ?> >09</OPTION>
<OPTION VALUE="10" <?php if($hour == '10') { echo "SELECTED"; } ?> >10</OPTION>
<OPTION VALUE="11" <?php if($hour == '11') { echo "SELECTED"; } ?> >11</OPTION>
<OPTION VALUE="12" <?php if($hour == '12') { echo "SELECTED"; } ?> >12</OPTION>
<OPTION VALUE="13" <?php if($hour == '13') { echo "SELECTED"; } ?> >13</OPTION>
<OPTION VALUE="14" <?php if($hour == '14') { echo "SELECTED"; } ?> >14</OPTION>
<OPTION VALUE="15" <?php if($hour == '15') { echo "SELECTED"; } ?> >15</OPTION>
<OPTION VALUE="16" <?php if($hour == '16') { echo "SELECTED"; } ?> >16</OPTION>
<OPTION VALUE="17" <?php if($hour == '17') { echo "SELECTED"; } ?> >17</OPTION>
<OPTION VALUE="18" <?php if($hour == '18') { echo "SELECTED"; } ?> >18</OPTION>
<OPTION VALUE="19" <?php if($hour == '19') { echo "SELECTED"; } ?> >19</OPTION>
<OPTION VALUE="20" <?php if($hour == '20') { echo "SELECTED"; } ?> >20</OPTION>
<OPTION VALUE="21" <?php if($hour == '21') { echo "SELECTED"; } ?> >21</OPTION>
<OPTION VALUE="22" <?php if($hour == '22') { echo "SELECTED"; } ?> >22</OPTION>
<OPTION VALUE="23" <?php if($hour == '23') { echo "SELECTED"; } ?> >23</OPTION>
</select>:<SELECT NAME="m">
<OPTION VALUE="00" <?php if(date('i') >= '50' && date('i') <= '59') { echo "SELECTED"; } ?> >00</OPTION>
<OPTION VALUE="10" <?php if(date('i') >= '00' && date('i') <= '09') { echo "SELECTED"; } ?> >10</OPTION>
<OPTION VALUE="20" <?php if(date('i') >= '10' && date('i') <= '19') { echo "SELECTED"; } ?> >20</OPTION>
<OPTION VALUE="30" <?php if(date('i') >= '20' && date('i') <= '29') { echo "SELECTED"; } ?> >30</OPTION>
<OPTION VALUE="40" <?php if(date('i') >= '30' && date('i') <= '39') { echo "SELECTED"; } ?> >40</OPTION>
<OPTION VALUE="50" <?php if(date('i') >= '40' && date('i') <= '49') { echo "SELECTED"; } ?> >50</OPTION>
</select>
</td>
</tr>
<tr>
<td></td>
<td colspan="2">
<input type="radio" name="timesel" value="depart" checked><span style="font-weight:normal;font-size:18px;"><?php echo $txt_depart; ?></span> 
<input type="radio" name="timesel" value="arrive"><span style="font-weight:normal;font-size:18px;"><?php echo $txt_arrive; ?></span>
</td>
</tr>
<tr><td colspan="3"></td></tr>
<tr>
<td colspan="3">
<div style="text-align:center;">
<button type="submit" name="submit" value="Search">Search</button>
<button type="button" onclick="javascript:switch_station()">Switch</button>
</div>
</td>
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

