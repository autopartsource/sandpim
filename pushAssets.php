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


if($assetpushuri)
{
 $allassets=$asset->getAssets('', 'startswith', 'any', 'any',  date('Y-m-d', strtotime('-90 day'))   , 'from', '', '', 99999);
 
 $data=array();
 foreach($allassets as $allasset)
 {
  $connectedparts=$asset->getPartsConnectedToAsset($allasset['assetid']);
  $connections=array();
  foreach ($connectedparts as $connectedpart)
  {
   $connections[]=array('partnumber'=>$connectedpart['partnumber'],'assettypecode'=>$connectedpart['assettypecode'],'sequence'=>$connectedpart['sequence'],'representation'=>$connectedpart['representation']);
  }
  $data[]=array('asset'=>$allasset,'connections'=>$connections);
 }

 $curl = curl_init($assetpushuri);
 curl_setopt($curl, CURLOPT_URL, $assetpushuri);
 curl_setopt($curl, CURLOPT_POST, true);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

 $resp = curl_exec($curl);
 curl_close($curl);

 $runtime=time()-$starttime;
 $logs->logSystemEvent('assetposter', 0, 'Asset poster pushed '.count($allassets).' asset metadata records in '.$runtime.' seconds');
}
else
{
 $logs->logSystemEvent('assetposter', 0, 'Asset poster uri (assetPushURI) is not set in config');    
}
?>