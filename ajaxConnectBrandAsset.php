<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');


$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxConnectBrandAsset.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
$asset=new asset();
$interchange=new interchange();

$assetoid='';
$connectionid='';
$success=false;

if(isset($_SESSION['userid']) && isset($_GET['assetid']) && isset($_GET['brandid']) && isset($_GET['assettypecode']) && isset($_GET['sequence']))
{
 $assetid=$_GET['assetid'];
 $brandid=$_GET['brandid'];
 $assettypecode=$_GET['assettypecode'];
 $sequence=intval($_GET['sequence']);
 $userid=$_SESSION['userid'];

 $assetoid=$asset->updateAssetOID($assetid);
 $connectionid=$asset->connectBrandToAsset($brandid, $assetid, $assettypecode, $sequence);
 $success=true;
  
 $asset->logAssetEvent($assetid, $userid, 'brand ['.$brandid.'] was connected', $assetoid);
 
 $result=array('success'=>$success,'connectionid'=>$connectionid,'assetoid'=>$assetoid);

 echo json_encode($result);
}
?>
