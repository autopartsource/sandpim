<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
session_start();
$pim= new pim;
$asset=new asset;

$fp = fopen('/var/www/html/logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['assetid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $assetid=$_GET['assetid'];
 $userid=$_SESSION['userid'];

 switch($_GET['elementid'])
 {
  case 'description':
  if(isset($_GET['description']))
  {
   $asset->setAssetDescription($assetid,$_GET['description']);
  // $pim->logAppEvent($appid,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'public':
  $asset->toggleAssetPublic($assetid);
      
//  $pim->logAppEvent($appid,$userid,'cosmetic toggled',$oid);
  break;

  case 'uripublic':
  $asset->toggleAssetUriPublic($assetid);
  //$pim->logAppEvent($appid,$userid,'cosmetic toggled',$oid);
  break;


  default:
  break;
 }

 echo $oid;
}?>
