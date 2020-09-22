<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
session_start();
$pim= new pim;
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
