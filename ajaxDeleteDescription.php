<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeleteDescription.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $descriptionrecord=$pim->getPartDescriptionByID($id);
 if($descriptionrecord)
 {
  if($descriptionrecord['partnumber']==$partnumber)
  {
   if($pim->deletePartDescriptionById($id))
   {
    $oid=$pim->updatePartOID($partnumber);
    $eventtext='description ['.$descriptionrecord['descriptioncode'].'('.$descriptionrecord['languagecode'].'):'.$descriptionrecord['description'].'] was deleted';  
    $success=true;
    $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
