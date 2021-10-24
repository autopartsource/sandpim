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
 $allassets=$asset->getAssets('', 'startswith', 'any', 'any',  '2000-01-01' , 'any', '', '', 0);
 $localoids=array(); foreach($allassets as $allasset){$localoids[]=$allasset['oid'];}
 sort($localoids);
 $localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
 $localoidhash= md5($localoidliststring);
 
 
 //ask server for a hash of its oids
 
 $curl = curl_init($assetpushuri.'?detail=hash');
 curl_setopt($curl, CURLOPT_URL, $assetpushuri.'?detail=hash');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 if(!isset($responsedecoded['hash']))
 {
  $logs->logSystemEvent('assetpusher', 0, 'unexpected response form remote system:'.$resp);    
  exit;
 }
 

 if($localoidhash == $responsedecoded['hash'])
 {
  $logs->logSystemEvent('assetpusher', 0, 'remote system reports same hash as local system - no action taken');
echo 'zero diffs';
  exit;
 }  
    
// remote system has a differnt hash of its oid's that we do. Ask for a list
 
 $curl = curl_init($assetpushuri.'?detail=ids');
 curl_setopt($curl, CURLOPT_URL, $assetpushuri.'?detail=ids');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 if(!isset($responsedecoded['oids']))
 {
  $logs->logSystemEvent('assetpusher', 0, 'unexpected response form remote system:'.$resp);    
  exit;
 }
 
 // we now have an array of oids from the other system
 $r=array(); foreach($responsedecoded['oids'] as $oid){$r[$oid]='';}
 $l=array(); foreach($localoids as $oid){$l[$oid]='';}
 
 $oidstopush=array();
 
 foreach($localoids as $oid)
 {
  if(!array_key_exists($oid,$r))
  {// this local oid is not found in the renote list
   $oidstopush[]=$oid;      
  }
 }
 
 $oidstodelete=array();
 foreach($responsedecoded['oids'] as $oid)
 {
  if(!array_key_exists($oid,$l))
  {// this remote oid is not found in the local list
   $oidstodelete[]=$oid;      
  }
 }
 
 echo 'push:'.print_r($oidstopush,true);
 
 echo '----<br/>';
 
 echo 'delete:'.print_r($oidstodelete,true);
 
 
 
 
 exit;
 
    
    
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
 $logs->logSystemEvent('assetpusher', 0, 'Asset pusher posted '.count($allassets).' records in '.$runtime.' seconds. Response: '.$resp);

// echo 'pushed '.count($data).' assets. Response:'.$resp; 
}
else
{
 $logs->logSystemEvent('assetpusher', 0, 'Asset pusher uri (assetPushURI) is not set in config');    
}
?>