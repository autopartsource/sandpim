<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
session_start();
$pim= new pim;
$padb= new padb;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);
$success=false;

if(isset($_SESSION['userid']) && isset($_GET['attributeid']) && isset($_GET['partnumber']) )
{
 $userid=$_SESSION['userid'];
 $attributeid=intval($_GET['attributeid']);
 $partnumber=$_GET['partnumber'];
 $attribute=$pim->getPartAttributeById($attributeid);
 if($attribute['partnumber']==$partnumber)
 {
  if($attribute['PAID'])
  {// this was a PAdb attribute
   $pim->deletePartAttribute($attributeid);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='PAdb attribute ['.$padb->PAIDname($attribute['PAID']).']='.$attribute['value'].$attribute['uom'].' was deleted';  
   $success=true;
  }
  else
  {// this was a user-defined attribute
   $pim->deletePartAttribute($attributeid);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='User-defined attribute ['.$attribute['name'].']='.$attribute['value'].$attribute['uom'].' was deleted';  
   $success=true;
  }
  $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  $result=array('success'=>$success,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
