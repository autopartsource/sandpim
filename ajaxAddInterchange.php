<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddInterchange.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$interchange= new interchange;

$result=array('success'=>false,'id'=>0,'oid'=>'','brandname'=>'???');

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['brand']) && isset($_GET['competitivepartnumber']))
{
 $partnumber=$_GET['partnumber'];
 $competitivepartnumber=$_GET['competitivepartnumber'];
 $brandAAIAID=$_GET['brand'];
 $userid=$_SESSION['userid'];

 if($pim->validPart($partnumber) && strlen($_GET['competitivepartnumber'])<=20 && strlen($_GET['brand'])==4)
 {
  if($id=$interchange->addInterchange($partnumber,$competitivepartnumber,$brandAAIAID,1,'EA','',''))
  {
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='competitor interchange ['.$brandAAIAID.':'.$competitivepartnumber.'] was added';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result['success']=true; $result['id']=$id; $result['oid']=$oid; $result['brandname']=$interchange->brandName($brandAAIAID);
 }
 echo json_encode($result);
}
?>
