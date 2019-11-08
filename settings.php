<?php
include_once('/var/www/html/class/pimClass.php');

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$pim=new pim;


?>
<!DOCTYPE html>
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="styles.css">
 </head>
 <body>
 <?php include('topnav.php');?>
  <h1>Settings</h1>
   <div style="padding-left:10px;">
    <div><a href="./users.php">User Maintenance</a></div>
    <div><a href="./config.php">Configuration Parameters</a></div>
    <div><a href="./pcdbBrowser.php">Manager PCdb favorite parttypes and positions</a></div>
   </div>
 </body>
</html>
