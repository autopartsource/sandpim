<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $appid=intval($_GET['appid']);
 $userid=$_SESSION['userid'];
 $app=$pim->getApp($appid);
 $oid=$app['oid'];

 switch($_GET['elementid'])
 {
  case 'status':
   $pim->setAppStatus($appid,intval($_GET['value']));
   $oid=$pim->getOIDofApp($appid);
   $pim->logAppEvent($appid,$userid,'status changed to:'.intval($_GET['value']),$oid);
   break;

  case 'parttypeid':
  if($app['parttypeid']!=$_GET['value'])
  {
   $pim->setAppParttype($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logAppEvent($appid,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'positionid':
  if($app['positionid']!=$_GET['value'])
  {
   $pim->setAppPosition($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logAppEvent($appid,$userid,'position changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'quantityperapp':
  if($app['quantityperapp']!=$_GET['value'])
  {
   $pim->setAppQuantity($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logAppEvent($appid,$userid,'quantity changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'cosmetic':
  $pim->toggleAppCosmetic($appid);
  $pim->logAppEvent($appid,$userid,'cosmetic toggled',$oid);
  break;

  case 'appcategory':
  if($app['appcategory']!=$_GET['value'])
  {
   $pim->setAppCategory($appid,intval($_GET['value']));
   $pim->logAppEvent($appid,$userid,'category changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'internalnotes':
   $pim->setAppInternalnotes($appid,$_GET['value']);
   $pim->logAppEvent($appid,$userid,'internal notes updated',$oid);
  break;

  default:
  break;
 }

 echo $oid;
}?>
