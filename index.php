<?php
include_once('/var/www/html/class/vcdbClass.php');
include_once('/var/www/html/class/pimClass.php');

$v=new vcdb;
$pim= new pim;

$part=$pim->getPart('sPRC914');
print_r($part);

?>
<!DOCTYPE html>
<html>
 <head>
 </head>
 <body>
<?php include('topnav.inc');?>
 <div style="border-style: groove;">
  <h1>Dashboard</h1>
 </div>
 </body>
</html>
