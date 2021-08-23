<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddDescription.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$result=array('success'=>false,'id'=>0,'oid'=>'','descriptiontext'=>'???','descriptioncode'=>'???');

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['descriptiontext']) && isset($_GET['descriptioncode']) && isset($_GET['languagecode']))
{
 $partnumber=$_GET['partnumber'];
 $descriptiontext=base64_decode($_GET['descriptiontext']);
 $descriptioncode=$_GET['descriptioncode'];
 $languagecode=$_GET['languagecode'];
 $userid=$_SESSION['userid'];

 if($pim->validPart($partnumber))
 {
  if($id=$pim->addPartDescription($partnumber,$descriptiontext,$descriptioncode,1,$languagecode))
  {
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='description ['.$descriptioncode.'('.$languagecode.'):'.$descriptiontext.'] was added';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result['success']=true; $result['id']=$id; $result['oid']=$oid; $result['descriptiontext']=$descriptiontext; $result['descriptioncode']=$descriptioncode;
 }
 echo json_encode($result);
}
?>
