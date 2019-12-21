<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']))
{
 //$fp = fopen('./logs/log.txt', 'a');fwrite($fp, $basevehicleid.','.$parttypeid.','.$positionid.','.$quantityperapp.','.$partnumber.','.$appcategory.','.$cosmetic."\n");fclose($fp);
 $part=$pim->getPart($_GET['partnumber']);
 echo json_encode($part);
}?>
