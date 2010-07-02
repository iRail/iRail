<?php
$v = `cd .. && git rev-parse --short HEAD`;
$c = `cd .. && git whatchanged`;
echo "<pre>" . $v . "</pre><br /><br /><br />";
echo "<pre>" . $c . "</pre>";
?>
