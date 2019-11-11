<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
$navCategory = 'reports';

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$vcdb=new vcdb;
$pim=new pim;

include('/var/www/html/includes/header.php');
?>

<div class="wrapper">
  <h1>Reports</h1>
</div>
<?php
include('/var/www/html/includes/footer.php');
?>