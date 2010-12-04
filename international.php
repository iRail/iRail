<?php 
/* 	Copyright 2008, 2009, 2010 Yeri "Tuinslak" Tiete (http://yeri.be), and others
    Copyright 2010 Pieter Colpaert (pieter@irail.be - http://bonsansnom.wordpress.com)

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

// international page

$from = $_COOKIE["intfrom"];
$to = $_COOKIE["intto"];
$lang = $_COOKIE["language"];

switch($lang) {
    case "EN":		$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_time = "Time:";
        $txt_arrive = "Arrival";
        $txt_depart = "Departure";
        break;
    case "NL":		$txt_from = "Van:";
        $txt_to = "Naar:";
        $txt_date = "Datum:";
        $txt_time = "Tijd:";
        $txt_arrive = "Aankomst";
        $txt_depart = "Vertrek";
        break;
    case "FR":      $txt_from = "De:";
        $txt_to = "Vers:";
        $txt_date = "Date:";
        $txt_time = "Heure:";
        $txt_arrive = "Arriv&#233;e";
        $txt_depart = "D&#233;part";
        break;
    case "DE":      $txt_from = "Von:";
        $txt_to = "Nach:";
        $txt_date = "Datum:";
        $txt_time = "Uhrzeit:";
        $txt_arrive = "Ankunft";
        $txt_depart = "Abfahrt";
        break;
    default:	$txt_from = "From:";
        $txt_to = "To:";
        $txt_date = "Date:";
        $txt_time = "Time:";
        $txt_arrive = "Arrival";
        $txt_depart = "Departure";
        break;
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>iRail - International</title>
        <link href="/css/mobile.css" rel="stylesheet">
        <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
        <meta name="keywords" content="nmbs, sncb, iphone, mobile, irail, irail.be, route planner">
        <meta name="description" content="NMBS/SNCB iPhone train route planner.">
        <meta http-equiv="Cache-control" content="no-cache">

        <script>
            function switch_station() {
                var tmp = document.getElementById("from").value;
                document.getElementById("from").value = document.getElementById("to").value;
                document.getElementById("to").value = tmp;
            }
        </script>

        <script>
            addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
            function hideAddressBar() { window.scrollTo(0, 1); }
        </script>
        <script src="/js/actb.js"></script>
        <script src="/js/common.js"></script>
        <script>
<?php
//this bit generates the stations list
include("includes/stationlist.php");
echo "var data=[";
generate_js_array($stations);
echo "];";
?>
        </script>
    </head>

    <body>
        <div class="container">
            <div class="toolbar anchorTop">
                <div class="title"><a href="international">iRail - International</a> </div>
                <div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="/img/i.png" alt="Settings" /></a></div>
                <br />
                <div class="toolbar">
                    <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
                        <?php echo date('l j/m/Y - H:i'); ?>
            <!--	/<span style="color:red;font-weight:bold;">/ NMBS site has probs.</span> -->
                    </div>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
                        <tr>
                            <form name="search" method="post" action="intresults">
                                <td>
                                    <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
                                           <tr>
                                            <td width="70"><?php echo $txt_from; ?></td>
                                            <td colspan="2"><input name="from" type="text" id="from" AUTOCOMPLETE="OFF" value="<?php echo $from; ?>"/>
                                                <script>
                                                    var obj = actb(document.getElementById('from'),data);
                                                    function reset_from() {
                                                        document.getElementById("from").value = "";
                                                    }
                                                </script>
                                                <a href="#" onclick="javascript:reset_from()"><img src="/img/x.png" alt="X" border="0" /></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo $txt_to; ?></td>
                                            <td colspan="2"><input name="to" type="text" id="to" AUTOCOMPLETE="OFF" value="<?php echo $to; ?>"/>
                                                <script>
                                                    var obj = actb(document.getElementById('to'),data);
                                                    function reset_to() {
                                                        document.getElementById("to").value = "";
                                                    }
                                                </script>
                                                <a href="#" onclick="javascript:reset_to()"><img src="/img/x.png" alt="X" border="0" /></a>
                                            </td>
                                        </tr>
                                        <tr><td colspan="3"><br /></td></tr>
                                        <tr>
                                            <td><?php echo $txt_date; ?></td>
                                            <td colspan="2">
                                                <select name="d">
                                                    <?php
                                                    for($i = 1; $i <= 31; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option value=\"". $number ."\"";
                                                        if(date('d') == $number) {
                                                            echo "SELECTED";
                                                        }
                                                        echo ">".$number."</option>";
                                                    }
                                                    ?>
                                                </select>/<select name="mo">
                                                    <?php
                                                    for($i = 1; $i <= 12; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option value=\"". $number ."\"";
                                                        if(date('m') == $number) {
                                                            echo "SELECTED";
                                                        }
                                                        echo ">".$number."</option>";
                                                    }
                                                    ?>
                                                </select>/<select name="y">
                                                    <option value="<?php echo date('y'); ?>" <?php if(date('y') == '10') {
    echo "SELECTED";
} ?> ><?php echo date('Y'); ?></option>
                                                    <option value="<?php echo date('y')+1; ?>" <?php if(date('y') == '11') {
    echo "SELECTED";
} ?> ><?php echo date('Y')+1; ?></option>
                                                </select></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo $txt_time; ?></td>
                                            <td colspan="2">
                                                <select name="h">
                                                    <?php
                                                    if(date('i') >= '50' && date('i') <= '59') {
                                                        $hour = date('H') + 1;
                                                    }else {
                                                        $hour = date('H');
                                                    }
                                                    for($i = 0; $i < 24; $i++) {
                                                        if($i < 10) {
                                                            $number = "0" . $i;
                                                        }else {
                                                            $number = $i;
                                                        }
                                                        echo "<option value=\"". $number ."\"";
                                                        if($hour == $number) {
                                                            echo "SELECTED";
    }
    echo ">".$number."</option>";
}
?>
                                                </select>:<select name="m">
                                                    <option value="00" <?php if(date('i') >= '50' && date('i') <= '59') {
    echo "SELECTED";
} ?> >00</option>
                                                    <option value="10" <?php if(date('i') >= '00' && date('i') <= '09') {
    echo "SELECTED";
} ?> >10</option>
                                                    <option value="20" <?php if(date('i') >= '10' && date('i') <= '19') {
    echo "SELECTED";
} ?> >20</option>
                                                    <option value="30" <?php if(date('i') >= '20' && date('i') <= '29') {
    echo "SELECTED";
} ?> >30</option>
                                                    <option value="40" <?php if(date('i') >= '30' && date('i') <= '39') {
    echo "SELECTED";
} ?> >40</option>
                                                    <option value="50" <?php if(date('i') >= '40' && date('i') <= '49') {
    echo "SELECTED";
} ?> >50</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td colspan="2">
                                                <input type="radio" name="timesel" value="depart" checked/><span style="font-weight:normal;font-size:18px;"><?php echo $txt_depart; ?></span>
                                                <input type="radio" name="timesel" value="arrive"/><span style="font-weight:normal;font-size:18px;"><?php echo $txt_arrive; ?></span>
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
                                        <tr><td colspan="3"><br /></td></tr>
                                        <tr>
                                            <td colspan="3">
                                                <table width="100%" border="0" align="center" style="text-align:center;">
                                                    <tr>
                                                        <td class="footer" width="50%"><a href="/">Nat</a></td>
                                                        <td class="footer" width="50%"><a href="international">Int</a></td>
                                                    </tr>
                                                </table>
                                            </td>
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
