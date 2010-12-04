<!DOCTYPE html>
<html>
    <body>
        <div data-role="page">
            <div data-role="header">
                <h1>{iRail} - {subtitle}</h1>
                <a href="#config" data-icon="gear" class="ui-btn-right">{i18n_options}</a>
            </div>
            <div class="contentText">
                <form action="feedback.php" method="post">
                    <input type="hidden" name="mode" value="insert" />
                    <table cellpadding="2" cellspacing="0" border="0">
                        <tr><td>Name</td><td><input type="text" class="feedbackInputTxt" name="name" /></td></tr>
                        <tr><td>E-mail</td><td><input type="text" class="feedbackInputTxt" name="email" /></td></tr>

                        <tr><td>Category</td><td><select name="category" class="feedbackSelect">
                                    <option value="suggestion" selected="selected">Suggestion</option>
                                    <option value="bug">Bug</option>
                                    <option value="other">other</option>
                                </select></td>

                        </tr>
                        <tr><td valign="top">Message</td><td><textarea name="message" class="feedbackMessage"></textarea></td>
                        </tr>
                    </table>
                    <input type="submit" value="Submit"/>
                </form>
            </div>
            <div data-role="footer">
                {footer}
            </div>
        </div>
    {GoogleAnalytics}
    </body>
</html>
