<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeleteExpi.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $expi=$pim->getPartEXPIbyId($id);
 if($expi)
 {
  if($expi['partnumber']==$partnumber)
  {
   $pim->deletePartEXPIbyId($id);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='EXPI code ['.$expi['EXPIcode'].'='.$expi['EXPIvalue'].'] was deleted';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
