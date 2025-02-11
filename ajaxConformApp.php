<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxConformApp.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['fitment']) && isset($_GET['positionandparttype']))
{
 // get the existing app for pre-comparison so we can know what to change
// $logs = new logs;

 $appid=intval($_GET['appid']);
 $neednewoid=false;
 $description='fitment grid drag';
 $userid=$_SESSION['userid'];

 if($app=$pim->getApp($appid))
 {
//      $logs->logSystemEvent('debug',0, 'ajaxConformApp.php fitment:'. $_GET['fitment']);

     
  $attributes=unserialize(base64_decode($_GET['fitment']));
  $positionandparttype=unserialize(base64_decode($_GET['positionandparttype']));
  $positionid=intval($positionandparttype['positionid']);
  $parttypeid=intval($positionandparttype['parttypeid']);

  if($app['positionid']!=$positionid)
  { // position id needs to be changed
   $pim->setAppPosition($appid,$positionid,false);
   $description.='; position changed from '.$app['positionid'].' to '.$positionid;
   $neednewoid=true;
  }

  if($app['parttypeid']!=$parttypeid)
  { // parttype id needs to be changed
   $pim->setAppParttype($appid,$parttypeid,false);
   $description.='; parttype changed from '.$app['parttypeid'].' to '.$parttypeid;
   $neednewoid=true;
  }

  if($app['attributeshash']!=$pim->appAttributesHash($attributes))
  {
   $pim->applyAppAttributes($appid,$attributes,false);
   $description.='; fitment changed from '.serialize($app['attributes']).' to '.serialize($attributes);
   $neednewoid=true;
  }

  if($neednewoid)
  {
   $OID=$pim->updateAppOID($appid);
   $pim->logAppEvent($appid,$userid,$description,$OID);
  }
 }
}?>
