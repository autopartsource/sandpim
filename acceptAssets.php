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

$bodyraw=file_get_contents('php://input');
$data= json_decode($bodyraw,true);

$newassetcount=0;
$existingassetcount=0;

if(count($data))
{
 foreach ($data as $a)
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

    
$runtime=time()-$starttime;

$logs->logSystemEvent('assetacceptor', 0, 'Asset acceptor created '.count($newassetcount).' assets records in '.$runtime.' seconds');   

?>