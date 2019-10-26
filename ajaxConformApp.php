<?php
include_once('/var/www/html/class/pimClass.php');
$pim= new pim;


if(isset($_GET['appid']) && isset($_GET['fitment']) && isset($_GET['positionandparttype']))
{
 // get the existing app for pre-comparison so we can know what to change
 $appid=intval($_GET['appid']);
 $neednewoid=false;

 if($app=$pim->getApp($appid))
 {
  $attributes=unserialize(base64_decode($_GET['fitment']));
  $positionandparttype=unserialize(base64_decode($_GET['positionandparttype']));
  $positionid=intval($positionandparttype['positionid']);
  $parttypeid=intval($positionandparttype['parttypeid']);

  if($app['positionid']!=$positionid)
  { // position id needs to be changed
$fp = fopen('./logs/log.txt', 'a');fwrite($fp, 'position changed to:'.$positionid."\n");fclose($fp);
   $pim->setAppPosition($appid,$positionid,false);
   $neednewoid=true;
  }

  if($app['parttypeid']!=$parttypeid)
  { // parttype id needs to be changed
$fp = fopen('./logs/log.txt', 'a');fwrite($fp, 'parttype changed to:'.$parttypeid."\n");fclose($fp);
   $pim->setAppParttype($appid,$parttypeid,false);
   $neednewoid=true;
  }

  if($app['attributeshash']!=$pim->appAttributesHash($attributes))
  {
$fp = fopen('./logs/log.txt', 'a');fwrite($fp, 'attributes changed to:'.print_r($attributes,true).'from:'.print_r($app['attributes'],true));fclose($fp);

   $pim->applyAppAttributes($appid,$attributes);
   $neednewoid=true;
  }

  if($neednewoid)
  {
   $pim->newoid();
  }
 }
}?>
