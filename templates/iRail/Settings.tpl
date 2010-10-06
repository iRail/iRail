<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{title} - {i18n_settings}</title>
        <link href="css/mobile.css" rel="stylesheet">
        <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
        <script>
            addEventListener('load', function() { setTimeout(hideAddressBar, 0); }, false);
            function hideAddressBar() { window.scrollTo(0, 1); }
        </script>
    </head>

    <body>
        <div class="container">
            <div class="toolbar anchorTop">
                <div class="title"><a href="..">{i18n_settings}</a> </div>
                <div style="text-align:right;float:right;margin-right:10px"><a href="settings"><img style="vertical-align:middle;" border="0" src="/img/i.png" alt="Settings"></a></div>
                <br>
                <div class="toolbar">
                    <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
                        {i18n_savedinacookie}
                    </div>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
                        <tr>
                        <form name="settings" method="post" action="save">
                            <td>
                                <table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF" style="color:#000000";>
                                       <tr>
                                        <td width="100">{i18n_language}</td>
                                        <td colspan="2"><select name="lang" id="lang">
                                                <option value="EN">English</option>
                                                <option value="NL">Nederlands</option>
                                                <option value="FR">Fran&#231;ais</option>
                                                <option value="DE">Deutsch</option>
                                            </select></td>
                                    </tr>
                                    <tr><td colspan="3"><br></td></tr>
                                    <tr>
                                        <td colspan="3"><div style="text-align:center;"><input type="submit" name="submit" value="{i18n_save}!"></div></td>
                                    </tr>
                                </table>
                            </td>
                        </form>
                        </tr></table>
{footer}
                </div></div></div>
    </body>
{GoogleAnalytics}
    <script>
        var ID = "";
        if("{lang}" == "EN"){
            ID= 0;
        }else if("{lang}" == "NL"){
            ID= 1;
        }else if("{lang}" == "FR"){
            ID= 2;
        }else if("{lang}" == "DE"){
            ID= 3;
        }
        document.getElementById("lang").options[ID].selected = true;
    </script>
</html>