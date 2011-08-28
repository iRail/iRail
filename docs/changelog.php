<!DOCTYPE html>
<html>
    <head>
        <link rel="apple-touch-icon" href="../img/irail.png" />
        <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">
        <meta name="viewport" content="width=320; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
        <meta name="keywords" content="nmbs, sncb, iphone, mobile, irail, irail.be, route planner">
        <meta name="language" content="en">
        <META NAME="DESCRIPTION" CONTENT="NMBS/SNCB iPhone train route planner.">
        <meta name="verify-v1" content="CKTzWOdgOxi/n81oG7ycuF/h8UKhX9OAhfmOA0nQ+Ts=" />
        <META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
        <title>
            iRail changelog
        </title>
    </head>
    <body>
        <?php
        include '../includes/getVersion.php';
        include '../includes/getChangelog.php';

        $irailChangelog = str_replace("<", "&#060;", $irailChangelog);
        $irailChangelog = str_replace(">", "&#062;", $irailChangelog);

        echo "<pre>iRail Git version: " . $irailVersion . "</pre>";
        echo "<pre>iRail Git changelog: <br /><br />" . $irailChangelog . "</pre>";
        ?>

        <?php
        include '../includes/googleAnalytics.php';
        ?>

    </body>
</html>
