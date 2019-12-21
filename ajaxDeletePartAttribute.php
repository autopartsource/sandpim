<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/padbClass.php');
session_start();
$pim= new pim;
$padb= new padb;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['attributeid']))
{
 $userid=$_SESSION['userid'];
 $attributeid=intval($_GET['attributeid']);
 $eventtext=' attribute ['.$attributeid.'] was releted';
// $oid=$pim->updatePartOID($partnumber);
 $id=$pim->deletePartAttribute($attributeid);
 //$pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
}
?>
