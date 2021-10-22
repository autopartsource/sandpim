<?php

/* POST json-encoded asset metadata and part-asset connections to a listening/receiving system
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');

$starttime=time();

$pim = new pim();
$asset=new asset();
$logs=new logs();
$configGet = new configGet;

$assetpushuri=$configGet->getConfigValue('assetPushURI');

$assetpushuri='https://aps.dev/sandpim/acceptAssets.php';
if($assetpushuri)
{
 $allassets=$asset->getAssets('', 'startswith', 'any', 'any',  date('Y-m-d', strtotime('-3 day'))   , 'from', '', '', 99999);
 $data=array();

 $assetidkeyedassets=array(); 
 foreach($allassets as $allasset)
 {
  $assetidkeyedassets[$allasset['assetid']][]=array('id'=>$allasset['id'],'assetid'=>$allasset['assetid'],'filename'=>$allasset['filename'],'localpath'=>$allasset['localpath'],'uri'=>$allasset['uri'],'orientationViewCode'=>$allasset['orientationViewCode'],'colorModeCode'=>$allasset['colorModeCode'],'assetHeight'=>$allasset['assetHeight'],'assetWidth'=>$allasset['assetWidth'],'dimensionUOM'=>$allasset['dimensionUOM'],'background'=>$allasset['background'],'fileType'=>$allasset['fileType'],'createdDate'=>$allasset['createdDate'],'public'=>$allasset['public'],'approved'=>$allasset['approved'],'description'=>$allasset['description'],'oid'=>$allasset['oid'],'fileHashMD5'=>$allasset['fileHashMD5'],'filesize'=>$allasset['filesize'],'resolution'=>$allasset['resolution'],'languagecode'=>$allasset['languagecode']);
 } 
 

 foreach($assetidkeyedassets as $assetid=>$assetrecords)
 {    
  $connectedparts=$asset->getPartsConnectedToAsset($assetid);
  $connections=array();
  foreach($connectedparts as $connectedpart)
  {
   $connections[]=array('partnumber'=>$connectedpart['partnumber'],'assettypecode'=>$connectedpart['assettypecode'],'sequence'=>$connectedpart['sequence'],'representation'=>$connectedpart['representation']);
  }
  $data[]=array('assetid'=>$assetid,'records'=>$assetrecords,'connections'=>$connections);
 }
 
 $curl = curl_init($assetpushuri);
 curl_setopt($curl, CURLOPT_URL, $assetpushuri);
 curl_setopt($curl, CURLOPT_POST, true);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

 $resp = curl_exec($curl);
 curl_close($curl);

 $runtime=time()-$starttime;
 $logs->logSystemEvent('assetposter', 0, '*'.$resp.'* Asset poster pushed '.count($allassets).' asset metadata records in '.$runtime.' seconds');

 echo 'pushed '.count($data).' assets. Response:'.$resp; 
}
else
{
 $logs->logSystemEvent('assetposter', 0, 'Asset pusher uri (assetPushURI) is not set in config');    
}
?>