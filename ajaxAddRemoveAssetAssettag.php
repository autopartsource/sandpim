<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveAssetAssettag.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$asset = new asset;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['assettagid']) && isset($_GET['assetid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $assettagid=intval($_GET['assettagid']);
 $assetid=$_GET['assetid'];
 $tagtext=$asset->assettagText($assettagid);
 $result['tagtext']=$tagtext;
 
 if($asset->validAsset($assetid))
 {
  switch($_GET['action'])
  {
   case 'remove':
    $asset->removeTagsFromAsset($assetid, $assettagid);
    $result['success']=true;
    $asset->logAssetEvent($assetid, $_SESSION['userid'], 'Assettag ['.$tagtext.'] removed from asset ['.$assetid.']' , '');
    break;

   case 'add':
    $result['id']=$asset->addAssetTagidToAsset($assetid, $assettagid);           
    $result['success']=true;
    $asset->logAssetEvent($assetid, $_SESSION['userid'], 'Assettag ['.$tagtext.'] added to asset ['.$assetid.']' , '');
    break;

   default:
    break;
  }
 }
 echo json_encode($result);
}