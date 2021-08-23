<?php
include_once('./class/pimClass.php');
include_once('./class/packagingClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeletePackage.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$packaging= new packaging;
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
