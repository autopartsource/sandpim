<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeletePartAttribute.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$padb= new padb;
$success=false;

if(isset($_SESSION['userid']) && isset($_GET['attributeid']) && isset($_GET['partnumber']) )
{
 $userid=$_SESSION['userid'];
 $attributeid=intval($_GET['attributeid']);
 $partnumber=$_GET['partnumber'];
 $attribute=$pim->getPartAttributeById($attributeid);
 if($attribute['partnumber']==$partnumber)
 { // verify that this id actualy belongs with this partnumber (for safty)
  if($attribute['PAID'])
  {// this was a PAdb attribute
   $pim->deletePartAttribute($attributeid);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='PAdb attribute ['.$padb->PAIDname($attribute['PAID']).'='.$attribute['value'].$attribute['uom'].'] was deleted';  
   $success=true;
  }
  else
  {// this was a user-defined attribute
   $pim->deletePartAttribute($attributeid);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='User-defined attribute ['.$attribute['name'].'='.$attribute['value'].$attribute['uom'].'] was deleted';  
   $success=true;
  }
  $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  $result=array('success'=>$success,'oid'=>$oid);
  
  // touch any dependant parts (change their oids and write history records)  
  $dependantparts=$pim->getPartnumbersByBasepart($partnumber);
  foreach($dependantparts as $dependantpart)
  {// each part that claims this one as a base
   $oid=$pim->updatePartOID($dependantpart);
   $pim->logPartEvent($dependantpart,$userid, $eventtext.' from basepart ['.$partnumber.']' ,$oid);  
  }

 }
 echo json_encode($result);
}
?>
