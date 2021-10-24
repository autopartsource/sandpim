<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
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
$newassetcount=0;  $droppedassetcount=0;

if(isset($_GET['detail']))
{ // get local list of all asset oid's
 $allassets=$asset->getAssets('', 'startswith', 'any', 'any', '2000-01-01', 'any', '', '',0);
 $oids=array(); foreach($allassets as $allasset){$oids[]=$allasset['oid'];}
 sort($oids);
 $oidliststring=''; foreach($oids as $oid){$oidliststring.=$oid;}

 if($_GET['detail']=='hash')
 {
  $hash=md5($oidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('assetacceptor', 0, 'client requested hash ('.$hash.') of oids');   
 }
 else
 {
  echo json_encode(array('oids'=>$oids));
  $logs->logSystemEvent('assetacceptor', 0, 'client requested list of ('.count($oids).') oids');
 }
}


$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

 if(isset($body['drops']))
 { // drop list is oid's (no assetid's)
  foreach ($body['drops'] as $oid)
  {
   $asset->deleteAssetRecordByOID($oid);
   $logs->logSystemEvent('assetacceptor', 0, 'dropped asset by oid '.$oid);
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

    // delete any assets and connections that may exist under this assetid
    $asset->deleteAssetsByAssetid($assetid);
    foreach($a['records'] as $assetrecord)
    {
     $id=$asset->addAsset($assetid, $assetrecord['filename'], $assetrecord['localpath'], $assetrecord['uri'], $assetrecord['orientationViewCode'], $assetrecord['colorModeCode'], $assetrecord['assetHeight'], $assetrecord['assetWidth'], $assetrecord['dimensionUOM'], $assetrecord['resolution'], $assetrecord['background'], $assetrecord['fileType'], $assetrecord['public'], $assetrecord['approved'], $assetrecord['description'], $assetrecord['oid'], $assetrecord['fileHashMD5'], $assetrecord['filesize'], $assetrecord['public'], $assetrecord['languagecode'], $assetrecord['createdDate']);
     $newassetcount++;
    }
    foreach($a['connections'] as $connection)
    {// write all the part-asset recs
     $asset->connectPartToAsset($connection['partnumber'], $assetid, $connection['assettypecode'], $connection['sequence'], $connection['representation']);
     $pim->logPartEvent($connection['partnumber'], 0, 'asset '.$assetid.' connected by acceptAsset API', '');
    }
   }
  }
 }
}
    
$runtime=time()-$starttime;
if($newassetcount || $runtime>10)
{
 $logs->logSystemEvent('assetacceptor', 0, 'Asset acceptor added '.$newassetcount.', dropped '.$droppedassetcount.' assets records in '.$runtime.' seconds');   
}
?>