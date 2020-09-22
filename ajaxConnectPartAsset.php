<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
session_start();
$pim= new pim;
$asset=new asset();

$partoid='';
$assetoid='';

if(isset($_SESSION['userid']) && isset($_GET['assetid']) && isset($_GET['partnumber']) && isset($_GET['assettypecode']) && isset($_GET['sequence']) && isset($_GET['representation']))
{
 $partnumber=$_GET['partnumber'];
 $assetid=$_GET['assetid'];
 $assettypecode=$_GET['assettypecode'];
 $sequence=$_GET['sequence'];
 $representation=$_GET['representation'];
 $userid=$_SESSION['userid'];

 $partoid=$pim->updatePartOID($partnumber);
 $assetoid=$asset->updateAssetOID($assetid);
 $asset->connectPartToAsset($partnumber,$assetid,$assettypecode,$sequence,$representation);
  
 $pim->logPartEvent($partnumber,$userid, 'asset ['.$assetid.'] was connected' ,$partoid);
 $asset->logAssetEvent($assetid, $userid, 'part ['.$partnumber.'] was connected', $assetoid);
 $result=array('success'=>true,'partoid'=>$partoid,'assetoid'=>$assetoid);

 echo json_encode($result);
}
?>
