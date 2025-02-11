<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateAttributeOfPart.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$padb= new padb;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['attribute']) && isset($_GET['value']))
{
 $userid=$_SESSION['userid'];
 $partnumber=$_GET['partnumber'];
 $attributename='';
 $value=''; if(isset($_GET['value'])){$value=$_GET['value'];}
 $uom=''; if(isset($_GET['uom'])){$uom=$_GET['uom'];}
 $id='';
 $success=false;
 $part=$pim->getPart($partnumber);
 if($part)
 { // valid part
  $oid=$part['oid'];

  $PAID=intval($_GET['attribute']);
  if($PAID!=0)
  { // this is a PAdb id
   $PAname=$padb->PAIDname($PAID);
   if($PAname)
   { // valid PAdb id
     if(!$pim->getPartAttribute($partnumber,$PAID,''))
     {
      $eventtext='PAdb attribute ['.$PAname.'='.$value.$uom.'] was added';
      $id=$pim->writePartAttribute($partnumber, $PAID, $attributename, $value, $uom);
      $oid=$pim->updatePartOID($partnumber);
      $success=true;
     }
     else
     { // Attribute ID is alread applied to part - update it
      $id=$pim->updatePartAttribute($partnumber, $PAID, $attributename, $value, $uom);
      $eventtext='PAdb attribute ['.$PAname.'] updated to: '.$value.$uom;
      $oid=$pim->updatePartOID($partnumber);
      $success=true;
     }
     $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  else
  {// this is a user-defined attribute
   $attributename=$_GET['attribute'];
   $PAname=$_GET['attribute'];
   if(!$pim->getPartAttribute($partnumber,0,$attributename))
   {
    $eventtext='user-defined attribute['.$attributename.'='.$value.$uom.'] was added';
    $id=$pim->writePartAttribute($partnumber, $PAID, $attributename, $value, $uom);
   }
   else
   {// user-defined attribute exists from this part - update it
    $eventtext='user-defined attribute['.$attributename.'] was updated to: '.$value.$uom;
    $id=$pim->updatePartAttribute($partnumber, 0, $attributename, $value, $uom);
   }
   $oid=$pim->updatePartOID($partnumber);
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   $success=true;
  }
  
  // touch any dependant parts (change their oids and write history records)  
  if($success)
  {
   $dependantparts=$pim->getPartnumbersByBasepart($partnumber);
   foreach($dependantparts as $dependantpart)
   {// each part that claims this one as a base
    $oid=$pim->updatePartOID($dependantpart);
    $pim->logPartEvent($dependantpart,$userid, $eventtext.' on basepart ['.$partnumber.']' ,$oid);  
   }
  }
  
  $result=array('success'=>$success, 'id'=>$id, 'PAID'=>$PAID, 'name'=>$PAname, 'value'=>$value, 'uom'=>$uom,'oid'=>$oid);
  echo json_encode($result);
 }
}
?>
