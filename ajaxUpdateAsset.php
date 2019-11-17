<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;
$asset=new asset;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

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
  // $pim->logHistoryEvent($appid,$userid,'parttype changed to:'.intval($_GET['value']),$oid);
  }
  break;

  case 'public':
  $asset->toggleAssetPublic($assetid);
      
//  $pim->logHistoryEvent($appid,$userid,'cosmetic toggled',$oid);
  break;

  case 'uripublic':
  $asset->toggleAssetUriPublic($assetid);
  //$pim->logHistoryEvent($appid,$userid,'cosmetic toggled',$oid);
  break;


  default:
  break;
 }

 echo $oid;
}?>
