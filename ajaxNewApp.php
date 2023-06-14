<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxNewApp.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['basevehicleid']) && isset($_GET['quantityperapp']) && isset($_GET['partnumber']) && isset($_GET['cosmetic']) && isset($_GET['fitment']) && isset($_GET['positionandparttype']))
{
 $userid=$_SESSION['userid'];
 $movetype=''; if(isset($_GET['movetype']) && ($_GET['movetype']=='entry' || $_GET['movetype']=='drag-copy')){$movetype=$_GET['movetype'];}
 $success=false;
 $message='';
 $partnumber=$pim->sanitizePartnumber($_GET['partnumber']);
 
 if($pim->validPart($partnumber))
 {
 
  $attributes=unserialize(base64_decode($_GET['fitment']));
  $positionandparttype=unserialize(base64_decode($_GET['positionandparttype']));
  $positionid=intval($positionandparttype['positionid']);
  $parttypeid=intval($positionandparttype['parttypeid']);
  $basevehicleid=intval($_GET['basevehicleid']);
  $quantityperapp=intval($_GET['quantityperapp']);
  $cosmetic=intval($_GET['cosmetic']);
  $newappid=$pim->newApp($basevehicleid,$parttypeid,$positionid,$quantityperapp,$partnumber,$cosmetic,$attributes,'');
  $oid=$pim->getOIDofApp($newappid);
  $pim->logAppEvent($newappid,$userid,'app created by fitment grid '.$movetype,$oid);
  $pim->logVehicleEvent($basevehicleid, $userid, $partnumber.' was connected at position '.$positionid);
  $success=true;
 }
 else
 { // given partnumber is not valid     
  $message='invalid partnumber ['.$partnumber.'] entered'; $oid=''; $newappid='';
 }
 
 echo json_encode(array('success'=>$success, 'message'=>$message, 'newappid'=>$newappid,'oid'=>$oid));
}?>
