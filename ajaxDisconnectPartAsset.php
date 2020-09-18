<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
session_start();
$pim= new pim;
$asset=new asset();

$oid='';

if(isset($_SESSION['userid']) && isset($_GET['connectionid']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $connectionid=intval($_GET['connectionid']);

 if($assetrecord=$asset->getAssetByPartConnectionid($connectionid))
 {
  $oid=$pim->updatePartOID($partnumber);
 
  $asset->disconnectPartFromAsset($partnumber,$connectionid);
  
  $pim->logPartEvent($partnumber,$userid, 'asset ['.$assetrecord['assetid'].'] was disconnected' ,$oid);
  $result=array('success'=>true,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
