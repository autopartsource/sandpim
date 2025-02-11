<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDisconnectPartAsset.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$asset=new asset();

$partoid='';
$assetoid='';

if(isset($_SESSION['userid']) && isset($_GET['connectionid']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $connectionid=intval($_GET['connectionid']);

 if($assetrecord=$asset->getAssetByPartConnectionid($connectionid))
 {
  $partoid=$pim->updatePartOID($partnumber);
  $assetoid=$asset->updateAssetOID($assetrecord['assetid']);
  $asset->disconnectPartFromAsset($partnumber,$connectionid);
  $pim->logPartEvent($partnumber,$userid, 'asset ['.$assetrecord['assetid'].'] was disconnected' ,$partoid);
  $asset->logAssetEvent($assetrecord['assetid'], $userid, 'part ['.$partnumber.'] was disconnected', $assetoid);
  $result=array('success'=>true,'partoid'=>$partoid,'assetoid'=>$assetoid);
 }
 echo json_encode($result);
}
?>
