<?php
include_once('./class/pimClass.php');
include_once('./class/packagingClass.php');
session_start();
$pim= new pim;
$packaging= new packaging;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $packagerecord=$packaging->getPackageById($id);
 if($packagerecord)
 {
  if($packagerecord['partnumber']==$partnumber)
  {
   if($packaging->deletePackageById($id))
   {
    $oid=$pim->updatePartOID($partnumber);
    $eventtext='package ['.$packagerecord['nicepackage'].'] was deleted';  
    $success=true;
    $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
