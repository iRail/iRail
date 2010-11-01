<!DOCTYPE html>
<html>
    <body>
        <div data-role="page">
            <div data-role="header">
                <h1>{iRail} - {subtitle}</h1>
                <a href="#config" data-icon="gear" class="ui-btn-right">{i18n_options}</a>
            </div>
            <div data-role="content">
                <div id="toolbar" style="height: 14px; padding: 2px; background-color: #efefef; text-align: center; color: #555; font-size: 12px; font-weight: normal;">
                        {i18n_savedinacookie}
                </div>
                <form name="settings" method="post" action="save">
                    <label>{i18n_language}</label><select name="lang" id="lang">
                        <option value="EN">English</option>
                        <option value="NL">Nederlands</option>
                        <option value="FR">Fran&#231;ais</option>
                        <option value="DE">Deutsch</option>
                    </select>
                    <input type="submit" name="submit" value="{i18n_save}!">
                </form>
            </div>
            <div data-role="footer">
                {footer}
                <a href="#national">{i18n_national}</a>
                <a href="#about">{i18n_about}</a>
            </div>
        </div>
    {GoogleAnalytics}
    </body>
</html>