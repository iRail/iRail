<?php
include 'includes/getVersion.php';
?>
<p style="color:#999999;font-size:12px;position:absolute;left:10px;">made by <a href="http://yeri.be/irail/" target="_blank">Yeri Tiete</a>; 
data from <a href="http://www.b-rail.be/" target="_blank">(B)</a>; 
version: <a href="changelog">
<?php 
$irailVersion = str_replace("\n", "", $irailVersion);
echo $irailVersion; 
?></a>.</p>