<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeleteInterchange.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$interchange= new interchange;
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $interchangerecord=$interchange->getInterchangeById($id);
 if($interchangerecord)
 {
  if($interchangerecord['partnumber']==$partnumber)
  {
   if($interchange->deleteInterchangeById($id))
   {
    $oid=$pim->updatePartOID($partnumber);
    $eventtext='competitor interchange ['.$interchangerecord['brandAAIAID'].':'.$interchangerecord['competitorpartnumber'].'] was deleted';  
    $success=true;
    $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 
 echo json_encode($result);
}
?>
