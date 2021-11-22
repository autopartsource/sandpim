<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateAsset.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$asset=new asset;

$oid='';

if(isset($_SESSION['userid']) && isset($_GET['assetrecordid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $assetrecordid=intval($_GET['assetrecordid']);
 $userid=intval($_SESSION['userid']);
 $value=$_GET['value'];
 
 if($a=$asset->getAssetById($assetrecordid))
 {
  
  switch($_GET['elementid'])
  {
   case 'description':
    $asset->setAssetDescription($assetrecordid,$value);
    $oid=$asset->updateAssetOIDbyRecordID($assetrecordid);
    $asset->logAssetEvent($a['assetid'], $userid, 'updated asset description', $oid);
    break;

   case 'public':
    $asset->toggleAssetPublic($assetrecordid);
    $oid=$asset->updateAssetOIDbyRecordID($assetrecordid);
    $asset->logAssetEvent($a['assetid'], $userid, 'toggled public attribute', $oid);
    break;

   case 'uripublic':
    $asset->toggleAssetUriPublic($assetrecordid);
    $oid=$asset->updateAssetOIDbyRecordID($assetrecordid);
    $asset->logAssetEvent($a['assetid'], $userid, 'toggled uripublic attribute', $oid);
    break;

   case 'assetlabel':
    $asset->setAssetLabel($assetrecordid, $value);
    $oid=$asset->updateAssetOIDbyRecordID($assetrecordid);
    $asset->logAssetEvent($a['assetid'], $userid, 'assetlabel updated to '.$value, $oid);
    break;

   default: break;
  }

  echo $oid;
 }
}?>
