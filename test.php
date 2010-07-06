<?php
	
$ch = curl_init("http://hari.b-rail.be/HAFAS/bin/query.exe/nn?seqnr=1&ident=k4.0538412.1278427697&OK#focus");
curl_setopt($ch, CURLOPT_POST      ,1);
curl_setopt($ch, CURLOPT_POSTFIELDS    , "");
curl_setopt($ch, CURLOPT_COOKIE, "__utma=1.1333636757.1252318191.1254576431.1255378610.7; __utmz=1.1278422421.7.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmc=1");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
curl_setopt($ch, CURLOPT_HEADER      ,0);  // DO NOT RETURN HTTP HEADERS
curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
$Rec_Data = curl_exec($ch);
 
//ob_start();
//header("Content-Type: text/html");
echo $Rec_Data;
$Final_Out=ob_get_clean();
echo "<br /><br /><br /><br /><br /><br /><br /><br />";
echo $Final_Out;
curl_close($ch);
 
 
	
	
/*	echo $url;
	echo $data;
	echo "<br /><br /><br /><br />";
	echo $body;
*/




?>