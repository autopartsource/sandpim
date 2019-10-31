<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['fitment']) && isset($_GET['positionandparttype']))
{
 // get the existing app for pre-comparison so we can know what to change
 $appid=intval($_GET['appid']);
 $neednewoid=false;
 $description='grid drag';
 $userid=$_SESSION['userid'];

 if($app=$pim->getApp($appid))
 {
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
   $pim->logHistoryEvent($appid,$userid,$description,$OID);
  }
 }
}?>
