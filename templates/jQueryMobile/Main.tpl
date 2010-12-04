<!--
This page is the page for the JQuery mobile site. It is written completely in html5 and should belightweight,
yet very fingerfriendly. Since it uses javascript it needs you to have a smartphone that enables javascript.
-->
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>{iRail} - {subtitle}</title>
        <link rel="stylesheet" href="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.css" />
        <script src="http://code.jquery.com/jquery-1.4.3.min.js"></script>
        <script src="http://code.jquery.com/mobile/1.0a1/jquery.mobile-1.0a1.min.js"></script>
        <script>
            function switch_station() {
         var tmp = document.getElementById("from").value;
         document.getElementById("from").value = document.getElementById("to").value;
         document.getElementById("to").value = tmp;
      }
            addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
            function hideAddressBar() { window.scrollTo(0, 1); }
        </script>
    </head>
    <body>

        <div data-role="page" id="national">
            <div data-role="header">
                <h1>{iRail} - {subtitle}</h1>
                <a href="#config" data-icon="gear" class="ui-btn-right">{i18n_options}</a>
            </div>
            <div data-role="content">

                <form method="post" action="query_nat.php" name="form">
                    <label for="from">{i18n_from}:</label>
                    <input name="from" data-type="search" type="text" id="from" AUTOCOMPLETE="OFF" value="{from}">

                    <label for="to">{i18n_to}:</label>
                    <input name="to" data-type="search" type="text" id="to" AUTOCOMPLETE="OFF" value="{to}">

                    <div data-role="collapsible" data-state="collapsed">
                        <h3>{i18n_setdatetime}</h3>
                        <label for="timeselectd">{i18n_date}:</label>
                        <div class="ui-grid-b">
                            <div class="ui-block-a">
                                <!--<fieldset data-role="controlgroup" data-type="vertical">
                                <input type="button" onclick="increment('timeselectd', 1);" value="+"/>
                                <input type="button" name="d" id="timeselectd"/>
                                <input type="button" onclick="increment('timeselectd', -1);" value="-" />
                                </fieldset>-->
                                <select NAME="d" id="timeselectd">
                                    <option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option>
                                </select>
                            </div>

                            <div class="ui-block-b">
                                <select NAME="mo" id="timeselectmo">
                                    <option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option>
                                </select>
                            </div>
                            <div class="ui-block-c">
                                <select name="y">
                                    <option value="10">2010</option>
                                    <option value="11">2011</option>
                                </select>
                            </div>
                        </div>

                        <label for="timeselecth">{i18n_time}:</label>
                        <div class="ui-grid-a">
                            <div class="ui-block-a">
                                <select NAME="h" id="timeselecth">
                                    <option value="00">00</option><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option>
                                </select>
                            </div>
                            <div class="ui-block-b">
                                <select NAME="m" id="timeselectm">
                                    <option VALUE="00">00</option>
                                    <option VALUE="10">10</option>
                                    <option VALUE="20">20</option>
                                    <option VALUE="30">30</option>
                                    <option VALUE="40">40</option>
                                    <option VALUE="50">50</option>
                                </select>
                            </div>
                        </div>
                        <div data-role="fieldcontain">
                            <fieldset data-role="controlgroup" data-type="horizontal">
                                <input type="radio" name="timesel" value="depart" id="timeseldep" checked><label for="timeseldep">{i18n_depart}</label>
                                <input type="radio" name="timesel" value="arrive" id="timeselarr"><label for="timeselarr">{i18n_arrival}</label>
                            </fieldset>
                        </div>
                    </div>
                    <div class="ui-grid-c">
                        <div class="ui-block-a">
                            <button type="button" id="switch" data-icon="back" onclick="javascript:switch_station()">{i18n_switch}</button>
                        </div>
                        <div class="ui-block-b">
                            <button type="submit" name="submit" value="Search" data-icon="check" data-iconpos="right">{i18n_search}</button>
                        </div>
                    </div>
                </form>

            </div>
            <div data-role="footer">
                {footer}
            </div>
        </div>

        <div data-role="page" id="config">
            <div data-role="header">
                <h1>{iRail} - {i18n_options}</h1>
                <!--<a href="#config" data-icon="gear" class="ui-btn-right">{i18n_options}</a>-->
            </div>
            <div data-role="content">
                <form name="settings" method="post" action="../save">
                    Choose your language
                    <select name="lang" id="lang">
                        <option value="EN">English</option>
                        <option value="NL">Nederlands</option>
                        <option value="FR">Fran&#231;ais</option>
                        <option value="DE">Deutsch</option>
                    </select>
                    <input type="submit" name="submit" value="Save!">
                </form>
            </div>
            <div data-role="footer">
           {footer}
            </div>
        </div>
    {GoogleAnalytics}
        <!-- This piece of code is to automatically set date and time to today -->
        <script>
            function increment(id, value){
                document.getElementById(id).value = parseInt(document.getElementById(id).value) + value;
                
            }
            var d = (new Date()).getDate();
            if(d.length != 2){
              //  d = "0" + ((new Date()).getDate()).toString();
            }
            document.getElementById("timeselectd").value =d;
            document.getElementById("timeselectmo").options[(new Date()).getMonth()].selected = true;
            document.getElementById("timeselecth").options[(new Date()).getHours()].selected = true;
            var minuteIndex = Math.floor(parseInt((new Date()).getMinutes())/10);
            document.getElementById("timeselectm").options[minuteIndex].selected = true;
        </script>
    </body>
</html>
