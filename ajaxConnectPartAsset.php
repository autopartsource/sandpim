<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxConnectPartAsset.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$asset=new asset();

$partoid='';
$assetoid='';
$connectionid='';
$success=false;

if(isset($_SESSION['userid']) && isset($_GET['assetid']) && isset($_GET['partnumber']) && isset($_GET['assettypecode']) && isset($_GET['sequence']) && isset($_GET['representation']))
{
 $partnumber=strtoupper($_GET['partnumber']);
 
 if($pim->validPart($partnumber))
 {
  $assetid=$_GET['assetid'];
  $assettypecode=$_GET['assettypecode'];
  $sequence=$_GET['sequence'];
  $representation=$_GET['representation'];
  $userid=$_SESSION['userid'];

  $partoid=$pim->updatePartOID($partnumber);
  $assetoid=$asset->updateAssetOID($assetid);
  $connectionid=$asset->connectPartToAsset($partnumber,$assetid,$assettypecode,$sequence,$representation);
  $success=true;
  
  $pim->logPartEvent($partnumber,$userid, 'asset ['.$assetid.'] was connected' ,$partoid);
  $asset->logAssetEvent($assetid, $userid, 'part ['.$partnumber.'] was connected', $assetoid);
 }
 
 $result=array('success'=>$success,'connectionid'=>$connectionid,'partoid'=>$partoid,'assetoid'=>$assetoid);

 echo json_encode($result);
}
?>
