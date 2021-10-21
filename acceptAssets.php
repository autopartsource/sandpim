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

$assetid='not found';
if(count($data))
{
 foreach ($data as $a)
 {
  if(array_key_exists('assetid', $a) && array_key_exists('records', $a))
  {
   // see if the assets already exists here
      
    $assetid=$a['assetid'];
    $assetrecords=$a['records'];
    foreach($assetrecords as $assetrecord)
    {
     $localassetrecords=$asset->getAssetRecordsByAssetid($assetid);
     if(count($localassetrecords))
     {// this assetid (like 'MG4121') already exists - maybe multiple varients of the same id
       $logs->logSystemEvent('assetacceptor', 0, 'asset records ('.count($localassetrecords).') already exist for '.$assetid.' - no action taken.');        
         
         
     }
     else
     {// this asset id is not found locally
      $id=$asset->addAsset($assetid, $assetrecord['filename'], $assetrecord['localpath'], $assetrecord['uri'], $assetrecord['orientationViewCode'], $assetrecord['colorModeCode'], $assetrecord['assetHeight'], $assetrecord['assetWidth'], $assetrecord['dimensionUOM'], $assetrecord['resolution'], $assetrecord['background'], $assetrecord['fileType'], $assetrecord['public'], $assetrecord['approved'], $assetrecord['description'], $assetrecord['oid'], $assetrecord['fileHashMD5'], $assetrecord['filesize'], $assetrecord['public'], $assetrecord['languagecode'], $assetrecord['createddate']);
      $logs->logSystemEvent('assetacceptor', 0, 'asset record '.$id.' was written for '.$assetid);        
     }
    }
  }
 }
}



    
$runtime=time()-$starttime;
$logs->logSystemEvent('assetacceptor', 0, $assetid);   

//$logs->logSystemEvent('assetacceptor', 0, print_r($postbody,true).': Asset acceptor received '.count($assets).' asset metadata records in '.$runtime.' seconds');   

?>