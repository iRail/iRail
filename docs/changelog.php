<?php
include '../includes/getVersion.php';
include '../includes/getChangelog.php';

echo "<pre>Git version: " . $irailVersion . "</pre>";
echo "<pre>Git changelog: <br /><br />" . $irailChangelog . "</pre>";
?>
