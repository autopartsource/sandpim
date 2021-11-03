<?php

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();
$configGet = new configGet;

$uri=$configGet->getConfigValue('assetPushURI');

$uri='https://aps.dev/sandpim/acceptApps.php';
$pushlimit=5000;

if($uri)
{
 $localoids=$pim->getAppOids();
 sort($localoids);
 $localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
 $localoidhash= md5($localoidliststring);
 
 
 //ask server for a hash of its oids
 
 $curl = curl_init($uri.'?detail=hash');
 curl_setopt($curl, CURLOPT_URL, $uri.'?detail=hash');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 if(!isset($responsedecoded['hash']))
 {
  $logs->logSystemEvent('apppusher', 0, 'unexpected response form remote system:'.$resp);    
  exit;
 }
 
 

 if($localoidhash == $responsedecoded['hash'])
 {
  $logs->logSystemEvent('apppusher', 0, 'remote hash equals local hash - no apps pushed');
  exit;
 }  
    
// remote system has a differnt hash of its oid's that we do. Ask for an actual list
 
 $curl = curl_init($uri.'?detail=ids');
 curl_setopt($curl, CURLOPT_URL, $uri.'?detail=ids');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 //print_r($responsedecoded);
 
 
 if(!isset($responsedecoded['oids']))
 {
  $logs->logSystemEvent('apppusher', 0, 'unexpected response form remote system:'.$resp);    
  exit;
 }
 
 // we now have an array of oids from the remote (secondary) system
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
 
  // convert the "push" list of OID's into app object
 $appstopush=array();
 foreach($oidstopush as $oid)
 {
  if(count($appstopush)>=$pushlimit){break;}
  if($a=$pim->getAppByOID($oid))
  {
   $appstopush[]=$a;
  }
 }

 
 // compare sets of oids to determine what's extra in remote system 
 // don't bother converting them to real apps - they may not exist locally
 $oidstodrop=array();
 foreach($responsedecoded['oids'] as $oid)
 {
  if(!array_key_exists($oid,$l))
  {// this remote oid is not found in the local list
   $oidstodrop[]=$oid;
  }
 }

    
 if(count($appstopush)>0 || count($oidstodrop)>0)
 {
  $body=array('adds'=>$appstopush,'drops'=>$oidstodrop);
 
  $curl = curl_init($uri);
  curl_setopt($curl, CURLOPT_URL, $uri);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $headers = array("Accept: application/json","Content-Type: application/json",);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
  $resp=curl_exec($curl);
  curl_close($curl);
  $runtime=time()-$starttime;
  $logs->logSystemEvent('apppusher', 0, 'App pusher posted '.count($appstopush).' apps in '.$runtime.' seconds. '.$resp);
 }
}
else
{
 $logs->logSystemEvent('apppusher', 0, 'App pusher uri (assetPushURI) is not set in config');    
}
?>