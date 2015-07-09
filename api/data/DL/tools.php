<?php
//echo "(103813,192053)<br/>\n";
//var_dump(tools::LambertToWGS84(103813,192053));
//$arr  = tools::LambertToWGS84(103813,192053);
//echo "\n<br>";
//var_dump(tools::WGS84ToLambert($arr[0],$arr[1]));

class tools{

     //Lambert transformation algorithm constants
     private static $a,$f,$x0,$y0,$e,$p0,$p1,$p2,$l0,$m1,$m2,$t1 ,$t2 ,$t0 ,$n,$g ,$r0;

    //Initiate constants
     private static function initvars(){
	  tools::$a=6378388;
	  tools::$f=1/297;
	  tools::$x0=150000.013;
	  tools::$y0=5400088.438;
	  tools::$e = sqrt(2*tools::$f-tools::$f*tools::$f);
	  tools::$p0=deg2rad(90);
	  tools::$p1=deg2rad(49.83333367);
	  tools::$p2=deg2rad(51.166664006);
	  tools::$l0=deg2rad(4.367158666);
	  tools::$m1= cos(tools::$p1)/sqrt(1-tools::$e*tools::$e*sin(tools::$p1)*sin(tools::$p1));
	  tools::$m2= cos(tools::$p2)/sqrt(1-tools::$e*tools::$e*sin(tools::$p2)*sin(tools::$p2));
	  tools::$t1 = tan(pi()/4-tools::$p1/2)/pow((1-tools::$e*sin(tools::$p1))/(1+tools::$e*sin(tools::$p1)), tools::$e/2);
	  tools::$t2 = tan(pi()/4-tools::$p2/2)/pow((1-tools::$e*sin(tools::$p2))/(1+tools::$e*sin(tools::$p2)), tools::$e/2);
	  tools::$t0 = tan(pi()/4-tools::$p0/2)/pow((1-tools::$e*sin(tools::$p0))/(1+tools::$e*sin(tools::$p0)), tools::$e/2);
	  tools::$n= (log(tools::$m1)-log(tools::$m2))/(log(tools::$t1)-log(tools::$t2));
	  tools::$g = tools::$m1/(tools::$n*pow(tools::$t1,tools::$n));
	  tools::$r0=tools::$a*tools::$g*pow(tools::$t0,tools::$n);
     }
     
     public static function LambertToWGS84($x,$y){
	  tools::initvars();
	  //calc
	  $r = sqrt( ($x-tools::$x0)*($x-tools::$x0) + (tools::$r0-($y-tools::$y0))*(tools::$r0-($y-tools::$y0)));
	  $t = pow(($r/(tools::$a*tools::$g)),1/tools::$n);
	  $theta = atan(($x-tools::$x0)/(tools::$r0-$y+tools::$y0));
	  $lambda = ($theta/tools::$n)+tools::$l0;
	  $phi = pi()/2 - 2 * atan($t);//this is a wild guess
	  //we're going to make this guess better on each iteration
	  for($i = 0; $i< 10; $i++){ //10 ought to be enough for anyone?
	       $phi = pi()/2 - 2 * atan($t * pow((1-tools::$e*sin($phi))/(1+tools::$e*sin($phi)), tools::$e/2));
	  }
	  return array(rad2deg($phi),rad2deg($lambda));
     }

     public static function WGS84ToLambert($phi,$lambda){
	  tools::initvars();
	  $phi = deg2rad($phi);
	  $lambda = deg2rad($lambda);
	  //calc
	  $t = tan(pi()/4-$phi/2)/pow((1-tools::$e*sin($phi))/(1+tools::$e*sin($phi)), tools::$e/2);
	  $r = tools::$a * tools::$g * pow($t,tools::$n);
	  $theta = tools::$n*($lambda - tools::$l0);
	  $x = tools::$x0+$r*sin($theta);
	  $y = tools::$y0+tools::$r0-$r*cos($theta);
	  return array($x,$y);
     }
}

?>