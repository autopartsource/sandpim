<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $appid=intval($_GET['appid']);
 $elementid=intval($_GET['elementid']);
 $userid=$_SESSION['userid'];
 $app=$pim->getApp($appid);
 $oid=$app['oid'];

 switch($_GET['elementid'])
 {
  case 'status':
  if(isset($_GET['status']))
  {
   $pim->setAppStatus($appid,intval($_GET['status']));
  }
  break;

  case 'parttypeid':
  if($app['parttypeid']!=$_GET['value'])
  {
   $pim->setAppParttype($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logHistoryEvent($appid,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'positionid':
  if($app['positionid']!=$_GET['value'])
  {
   $pim->setAppPosition($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logHistoryEvent($appid,$userid,'position changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'quantityperapp':
  if($app['quantityperapp']!=$_GET['value'])
  {
   $pim->setAppQuantity($appid,intval($_GET['value']),true);
   $oid=$pim->getOIDofApp($appid);
   $pim->logHistoryEvent($appid,$userid,'quantity changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'cosmetic':
  $pim->toggleAppCosmetic($appid);
  $pim->logHistoryEvent($appid,$userid,'cosmetic toggled',$oid);
  break;

  case 'appcategory':
  if($app['appcategory']!=$_GET['value'])
  {
   $pim->setAppCategory($appid,intval($_GET['value']));
   $pim->logHistoryEvent($appid,$userid,'category changed to:'.intval($_GET['value']),$oid);
  }
  break;

  default:
  break;
 }

 echo $oid;
}?>
