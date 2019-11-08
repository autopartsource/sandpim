<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim=new pim;

?>
<!DOCTYPE html>
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="styles.css">
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Imoprt/Export</h1>
  <div style="padding:10px;"><a href="importACESsnippet.php">Import Small ACES xml text</a></div>
  <div style="padding:10px;"><a href="importACESupload.php">Upload & import ACES xml file</a></div>
  <div style="padding:10px;"><a href="importPartData.php">Import Part data from structured text</a></div>
  <div style="padding:10px;"><a href="exportPIESselect.php">Export PIES xml file</a></div>
 </body>
</html>
