<?php
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim=new pim;

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Configuration Parameters</h1>
 </body>
</html>
