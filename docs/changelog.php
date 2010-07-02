<?php
$v = `cd .. && git rev-parse --short HEAD`;
$c = `cd .. && git whatchanged`;
echo "<pre>Git version: " . $v . "</pre><br /><br />Changelog: <br />";
echo "<pre>" . $c . "</pre>";
?>
