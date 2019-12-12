<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 //$fp = fopen('./logs/log.txt', 'a');fwrite($fp, $basevehicleid.','.$parttypeid.','.$positionid.','.$quantityperapp.','.$partnumber.','.$appcategory.','.$cosmetic."\n");fclose($fp);
 $app=$pim->getApp(intval($_GET['appid']));
 echo json_encode($app);
}?>
