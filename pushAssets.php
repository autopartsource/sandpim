<?php

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
  $logs->logSystemEvent('assetpusher', 0, 'remote hash equals local hash - no assets pushed');
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
 
 
 // compare sets of oids to determine what's missing fron remote system
 $oidstopush=array();
 foreach($localoids as $oid)
 {
  if(!array_key_exists($oid,$r))
  {// this local oid is not found in the renote list
   $oidstopush[]=$oid;      
  }
 }
 
  // convert the "push" list of OID's into asset records
 $assetstopush=array();
 foreach($oidstopush as $oid)
 {
  if($a=$asset->getAssetByOID($oid))
  {
    $assetstopush[]=$a;
  }
 }

 
 // compare sets of oids to determine what's extra in remote system 
 // don't bother converting them to real assetsid - they may not exit locally
 $oidstodrop=array();
 foreach($responsedecoded['oids'] as $oid)
 {
  if(!array_key_exists($oid,$l))
  {// this remote oid is not found in the local list
   $oidstodrop[]=$oid;      
  }
 }

    
 if(count($assetstopush)>0 || count($oidstodrop)>0)
 {
  $body=array('adds'=>array(),'drops'=>$oidstodrop);

  $assetidkeyedassets=array(); 
  foreach($assetstopush as $a)
  {
   $assetidkeyedassets[$a['assetid']][]=array('id'=>$a['id'],'assetid'=>$a['assetid'],'filename'=>$a['filename'],'localpath'=>$a['localpath'],'uri'=>$a['uri'],'orientationViewCode'=>$a['orientationViewCode'],'colorModeCode'=>$a['colorModeCode'],'assetHeight'=>$a['assetHeight'],'assetWidth'=>$a['assetWidth'],'dimensionUOM'=>$a['dimensionUOM'],'background'=>$a['background'],'fileType'=>$a['fileType'],'createdDate'=>$a['createdDate'],'public'=>$a['public'],'approved'=>$a['approved'],'description'=>$a['description'],'oid'=>$a['oid'],'fileHashMD5'=>$a['fileHashMD5'],'filesize'=>$a['filesize'],'resolution'=>$a['resolution'],'languagecode'=>$a['languagecode']);
  }
 
  foreach($assetidkeyedassets as $assetid=>$assetrecords)
  {    
   $connectedparts=$asset->getPartsConnectedToAsset($assetid);
   $connections=array();
   foreach($connectedparts as $connectedpart)
   {
    $connections[]=array('partnumber'=>$connectedpart['partnumber'],'assettypecode'=>$connectedpart['assettypecode'],'sequence'=>$connectedpart['sequence'],'representation'=>$connectedpart['representation']);
   }
   $body['adds'][]=array('assetid'=>$assetid,'records'=>$assetrecords,'connections'=>$connections);
  }
 
  $curl = curl_init($assetpushuri);
  curl_setopt($curl, CURLOPT_URL, $assetpushuri);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $headers = array("Accept: application/json","Content-Type: application/json",);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
  $resp = curl_exec($curl);
  curl_close($curl);

  $runtime=time()-$starttime;
  $logs->logSystemEvent('assetpusher', 0, 'Asset pusher posted '.count($assetstopush).' records in '.$runtime.' seconds. '.$resp);
 }
}
else
{
 $logs->logSystemEvent('assetpusher', 0, 'Asset pusher uri (assetPushURI) is not set in config');    
}
?>