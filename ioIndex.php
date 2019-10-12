<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

$v=new vcdb;
$pim= new pim;

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Imoprt/Export</h1>
  <div style="padding:10px;"><a href="importACESsnippet.php">Import Small ACES xml text</a></div>
  <div style="padding:10px;"><a href="importACESupload.php">Upload & import ACES xml file</a></div>
  <div style="padding:10px;"><a href="importPartData.php">Import Part data from structured text</a></div>
 </div>
 </body>
</html>
