<?php
include '../includes/getVersion.php';
include '../includes/getChangelog.php';

$irailChangelog = str_replace("<", "&#060;", $irailChangelog);
$irailChangelog = str_replace(">", "&#062;", $irailChangelog);

echo "<pre>Git version: " . $irailVersion . "</pre>";
echo "<pre>Git changelog: <br /><br />" . $irailChangelog . "</pre>";
?>
