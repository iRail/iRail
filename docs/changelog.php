<?php
include '../includes/getVersion.php';
include '../includes/getChangelog.php';

echo "<pre>Git version: " . $irailVersion . "</pre><br /><br />";
echo "<pre>Git changelog: <br />" . $irailChangelog . "</pre>";
?>
