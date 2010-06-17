<?php

$maintenance = 0;

if($maintenance == 0) {
	header('Location: http://irail.be/national');
}else{
	echo "Site currently down for maintenance. <br />We apologise for any inconvience this may cause.";
}

?>
