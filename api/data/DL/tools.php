<?php
//echo "(103813,192053)<br/>\n";
//var_dump(Tools::LambertToWGS84(103813,192053));
//$arr  = Tools::LambertToWGS84(103813,192053);
//echo "\n<br>";
//var_dump(Tools::WGS84ToLambert($arr[0],$arr[1]));

class Tools{

     //Lambert transformation algorithm constants
     private static $a,$f,$x0,$y0,$e,$p0,$p1,$p2,$l0,$m1,$m2,$t1 ,$t2 ,$t0 ,$n,$g ,$r0;

//Initiate constants
     private static function initvars(){
	  Tools::$a=6378388;
	  Tools::$f=1/297;
	  Tools::$x0=150000.013;
	  Tools::$y0=5400088.438;
	  Tools::$e = sqrt(2*Tools::$f-Tools::$f*Tools::$f);
	  Tools::$p0=deg2rad(90);
	  Tools::$p1=deg2rad(49.83333367);
	  Tools::$p2=deg2rad(51.166664006);
	  Tools::$l0=deg2rad(4.367158666);
	  Tools::$m1= cos(Tools::$p1)/sqrt(1-Tools::$e*Tools::$e*sin(Tools::$p1)*sin(Tools::$p1));
	  Tools::$m2= cos(Tools::$p2)/sqrt(1-Tools::$e*Tools::$e*sin(Tools::$p2)*sin(Tools::$p2));
	  Tools::$t1 = tan(pi()/4-Tools::$p1/2)/pow((1-Tools::$e*sin(Tools::$p1))/(1+Tools::$e*sin(Tools::$p1)), Tools::$e/2);
	  Tools::$t2 = tan(pi()/4-Tools::$p2/2)/pow((1-Tools::$e*sin(Tools::$p2))/(1+Tools::$e*sin(Tools::$p2)), Tools::$e/2);
	  Tools::$t0 = tan(pi()/4-Tools::$p0/2)/pow((1-Tools::$e*sin(Tools::$p0))/(1+Tools::$e*sin(Tools::$p0)), Tools::$e/2);
	  Tools::$n= (log(Tools::$m1)-log(Tools::$m2))/(log(Tools::$t1)-log(Tools::$t2));
	  Tools::$g = Tools::$m1/(Tools::$n*pow(Tools::$t1,Tools::$n));
	  Tools::$r0=Tools::$a*Tools::$g*pow(Tools::$t0,Tools::$n);
     }

     public static function LambertToWGS84($x,$y){
	  Tools::initvars();
	  //calc
	  $r = sqrt( ($x-Tools::$x0)*($x-Tools::$x0) + (Tools::$r0-($y-Tools::$y0))*(Tools::$r0-($y-Tools::$y0)));
	  $t = pow(($r/(Tools::$a*Tools::$g)),1/Tools::$n);
	  $theta = atan(($x-Tools::$x0)/(Tools::$r0-$y+Tools::$y0));
	  $lambda = ($theta/Tools::$n)+Tools::$l0;
	  $phi = pi()/2 - 2 * atan($t);//this is a wild guess
	  //we're going to make this guess better on each iteration
	  for($i = 0; $i< 10; $i++){ //10 ought to be enough for anyone?
	       $phi = pi()/2 - 2 * atan($t * pow((1-Tools::$e*sin($phi))/(1+Tools::$e*sin($phi)), Tools::$e/2));
	  }
	  return array(rad2deg($phi),rad2deg($lambda));
     }

     public static function WGS84ToLambert($phi,$lambda){
	  Tools::initvars();
	  $phi = deg2rad($phi);
	  $lambda = deg2rad($lambda);
	  //calc
	  $t = tan(pi()/4-$phi/2)/pow((1-Tools::$e*sin($phi))/(1+Tools::$e*sin($phi)), Tools::$e/2);
	  $r = Tools::$a * Tools::$g * pow($t,Tools::$n);
	  $theta = Tools::$n*($lambda - Tools::$l0);
	  $x = Tools::$x0+$r*sin($theta);
	  $y = Tools::$y0+Tools::$r0-$r*cos($theta);
	  return array($x,$y);
     }
}

?>
