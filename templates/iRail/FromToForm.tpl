<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"> <!-- is this normal? -->
        <title>{title}</title>
        <link href="templates/iRail/css/mobile.css" rel="stylesheet">

        <!-- What is this line for? Please document -->
        <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
        <meta name="keywords" content="nmbs, sncb, iphone, mobile, irail, irail.be, route planner">
        <meta name="description" CONTENT="NMBS/SNCB mobile iPhone train route planner.">

        <meta http-equiv="Cache-control" content="no-cache">


        <script>
            function switch_station() {
                var tmp = document.getElementById("from").value;
                document.getElementById("from").value = document.getElementById("to").value;
                document.getElementById("to").value = tmp;
            }
            addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
            function hideAddressBar() { window.scrollTo(0, 1); }
        </script>

        <script src="/js/actb.js"></script>
        <script src="/js/common.js"></script>

        <script>
            var data= [{stationarray}];
        </script>
    </head>

    <body>
        <div class="container">
            <div class="toolbar anchorTop">
                <div class="title"><a href="/">{title}</a> </div>
                <div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="/img/i.png" alt="Settings"></a></div>
                <br>
                <div class="toolbar">

                    <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
                        {date}                    </div>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
                        <tr>
                        <form name="search" method="post" action="results">
                            <td>
                                <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
                                       <tr>

                                        <td width="70">{i18n_from}:</td>
                                        <td colspan="2">
                                            <input name="from" type="text" id="from" AUTOCOMPLETE="OFF" value="{autofrom}">
                                            <script>
                                                var obj = actb(document.getElementById('from'),data);
                                                function reset_from() {
                                                    document.getElementById("from").value = '';
                                                }</script>
                                            <a href="#" onclick="javascript:reset_from()"><img src="/img/x.png" alt="X" border="0"></a>
                                        </td>
                                    </tr>
                                    <tr>

                                        <td>{i18n_to}:</td>
                                        <td colspan="2"><input name="to" type="text" id="to" AUTOCOMPLETE="OFF" value="{autoto}">
                                            <script>
                                                var obj = actb(document.getElementById('to'),data);
                                                function reset_to() {
                                                    document.getElementById("to").value = "";
                                                }
                                            </script>
                                            <a href="#" onclick="javascript:reset_to()"><img src="/img/x.png" alt="X" border="0"></a>
                                        </td>
                                    </tr>
                                    <tr><td colspan="3"><br></td></tr>
                                    <tr>

                                        <td>{i18n_date}:</td>
                                        <td colspan="2">
                                            <select NAME="d" id="timeselectd">
                                                <option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option>
                                            </select>/<select NAME="mo" id="timeselectmo">

                                                <option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option
                                            </select>/<select name="y">
                                                <option value="10"  >2010</option>

                                                <option value="11"  >2011</option>
                                            </select></td>
                                    </tr>
                                    <tr>
                                        <td>{i18n_time}:</td>
                                        <td colspan="2">
                                            <select NAME="h" id="timeselecth">
                                                <option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option>                                                
                                            </select>:<select NAME="m" id="timeselectm">
                                                <option VALUE="00">00</option>
                                                <option VALUE="10">10</option>
                                                <option VALUE="20">20</option>
                                                <option VALUE="30">30</option>
                                                <option VALUE="40">40</option>
                                                <option VALUE="50">50</option>

                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2">
                                            <input type="radio" name="timesel" value="depart" checked><span style="font-weight:normal;font-size:18px;">{i18n_depart}</span>
                                            <input type="radio" name="timesel" value="arrive"><span style="font-weight:normal;font-size:18px;">{i18n_arrival}</span>

                                        </td>
                                    </tr>
                                    <tr><td colspan="3"></td></tr>
                                    <tr>
                                        <td colspan="3">
                                            <div style="text-align:center;">
                                                <button type="submit" name="submit" value="Search">{i18n_search}</button>
                                                <button type="button" onclick="javascript:switch_station()">{i18n_switch}</button>

                                            </div>
                                        </td>
                                    </tr>
                                    <tr><td colspan="3"><br></td></tr>
                                    <tr>
                                        <td colspan="3">
                                            <table width="100%" border="0" align="center" style="text-align:center;">
                                                <tr>
                                                    <td class="footer" width="50%"><a href="national">Nat</a></td>

                                                    <td class="footer" width="50%"><a href="international">Int</a></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </form>

                        </tr>
                    </table>
                    {footer}

                </div>
            </div>
        </div>

        {GoogleAnalytics}


        <!-- This piece of code is to automatically set date and time to today -->
        <script>
            document.getElementById("timeselectd").options[(new Date()).getDate() -1].selected = true;
            document.getElementById("timeselectmo").options[(new Date()).getMonth()].selected = true;
            document.getElementById("timeselecth").options[(new Date()).getHours()].selected = true;
            var minuteIndex = Math.floor(parseInt((new Date()).getMinutes())/10);
            document.getElementById("timeselectm").options[minuteIndex].selected = true;
        </script>
    </body>
</html>