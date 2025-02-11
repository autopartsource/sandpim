<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/replicationClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptAssets - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$asset=new asset();
$replication = new replication();
$newassetcount=0;  $droppedassetcount=0;

if(isset($_GET['detail']))
{ // get local list of all asset oid's
 $allassets=$asset->getAssets('', 'startswith', 'any', 'any', '2000-01-01', 'any', '', '', '', 'startswith', '', 'startswith', 0);
 $oids=array(); foreach($allassets as $allasset){$oids[]=$allasset['oid'];}
 sort($oids);
 $oidliststring=''; foreach($oids as $oid){$oidliststring.=$oid;}

 if($_GET['detail']=='hash')
 {
  $hash=md5($oidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('replication', 0, 'gave hash ('.$hash.') of '.count($oids).' local asset oids to client '.$_SERVER['REMOTE_ADDR']);
 }
 else
 {
  echo json_encode(array('oids'=>$oids));
  $logs->logSystemEvent('replication', 0, 'gave list of '.count($oids).' local asset oids to client '.$_SERVER['REMOTE_ADDR']);
 }
}

$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

 if(!array_key_exists('identifier',$body) || !array_key_exists('signature',$body))
 {
  $logs->logSystemEvent('replication', 0, 'invalid data (missing identifier or signature) posted to acceptAssets API from client '.$_SERVER['REMOTE_ADDR']);
  exit;
 }

  // lookup peer by its claimed identifier
 $peers=$replication->getPeers($body['identifier'],'asset', 'primary');
 if(count($peers)==0)
 {
  $logs->logSystemEvent('replication', 0, 'unknown identifier ['.$body['identifier'].'] posted to acceptAsset API from client '.$_SERVER['REMOTE_ADDR']);  
  exit;
 }
 
 //test signature of payload
 $computedsignature = hash_hmac('SHA256', json_encode(array('identifier'=>$body['identifier'],'adds'=>$body['adds'],'drops'=>$body['drops'])), $peers[0]['sharedsecret'],false);
 if($body['signature']!=$computedsignature)
 {
  $logs->logSystemEvent('replication', 0, 'invalid signature on payload - no adds/drops accepted by acceptAssets API from peer identified by: '.$body['identifier']);
  exit;
 }
 
 if(isset($body['drops']))
 { // drop list is oid's (no assetid's)
  foreach ($body['drops'] as $oid)
  {
   $asset->deleteAssetRecordByOID($oid);
   $logs->logSystemEvent('replication', 0, 'dropped asset by oid '.$oid);
   $droppedassetcount++;
  }
 } 
 
 if(isset($body['adds']))
 {
  foreach ($body['adds'] as $a)
  {
   if(array_key_exists('assetid', $a) && array_key_exists('records', $a))
   {
    // see if the assets already exists here
    $assetid=$a['assetid']; 

    // delete any assets that may exist under this assetid
    $asset->deleteAssetsByAssetid($assetid);
    foreach($a['records'] as $assetrecord)
    {
     $id=$asset->addAsset($assetid, $assetrecord['filename'], $assetrecord['localpath'], $assetrecord['uri'], $assetrecord['orientationViewCode'], $assetrecord['colorModeCode'], $assetrecord['assetHeight'], $assetrecord['assetWidth'], $assetrecord['dimensionUOM'], $assetrecord['resolution'], $assetrecord['background'], $assetrecord['fileType'], $assetrecord['public'], $assetrecord['approved'], $assetrecord['description'], $assetrecord['oid'], $assetrecord['fileHashMD5'], $assetrecord['filesize'], $assetrecord['public'], $assetrecord['languagecode'],$assetrecord['assetlabel'] ,$assetrecord['createdDate'],1,1,1,1);
     $newassetcount++;
    }
   }
  }
 }
}
    
$runtime=time()-$starttime;
if($newassetcount || $runtime>10)
{
 $logs->logSystemEvent('replication', 0, 'Asset acceptor added '.$newassetcount.', dropped '.$droppedassetcount.' assets records in '.$runtime.' seconds');   
}
?>