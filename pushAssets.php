<?php

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/replicationClass.php');
include_once(__DIR__.'/class/logsClass.php');


$pim = new pim();
$asset=new asset();
$replication=new replication();
$logs=new logs();

$allassets=$asset->getAssets('', 'startswith', 'any', 'any',  '2000-01-01' , 'any', '', '','' ,'startswith', '', 'startswith', 0);
if(count($allassets)==0)
{
 echo "refusing to push an empty local list\r\n";
 $logs->logSystemEvent('replication', 0, 'local assets list is empty. No push completed (too risky).');
 exit;
}

$localoids=array(); foreach($allassets as $allasset){$localoids[]=$allasset['oid'];}
sort($localoids);
$localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
$localoidhash= md5($localoidliststring);
$l=array(); foreach($localoids as $oid){$l[$oid]='';}

  
$peers=$replication->getPeers('%','asset', 'secondary');

foreach($peers as $peer)
{
 $starttime=time();
 if($peer['enabled']==0){continue;}
 $uri=$peer['uri'];
 $pushlimit=$peer['objectlimit'];
 $logstring='uri: '.$uri.'; ';

 $logstring.='localoids '.count($localoids). '; ';
 if(count($localoids)!=count($l)){$logstring.='local distinct oid count ('.count($l).') is different than oid count! you have a duplicate oid; ';}

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
  $logs->logSystemEvent('replication', 0, 'unexpected response in pushAssets form '.$peer['description'].':'.$resp);    
  continue; // iterate to next peer
 }
 
 if($localoidhash == $responsedecoded['hash'])
 {
  // commented 11-11-2024 to bring the noise down  $logs->logSystemEvent('replication', 0, 'remote hash on '.$peer['description'].' equals local hash - no assets pushed');
  continue; // iterate to next peer
 }
    
// remote system has a differnt hash of its oid's that we do. Ask for a list 
 $curl = curl_init($uri.'?detail=ids');
 curl_setopt($curl, CURLOPT_URL, $uri.'?detail=ids');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 if(!isset($responsedecoded['oids']))
 {
  $logs->logSystemEvent('replication', 0, 'unexpected response in pushAssets form peer '.$peer['description'].':'.$resp);    
  continue; // iterate to next peer
 }
 
 // we now have an array of oids from the other system
 $r=array(); foreach($responsedecoded['oids'] as $oid){$r[$oid]='';}
 $logstring.='remote distinct oids: '.count($r).'; ';

 // compare sets of oids to determine what's missing fron remote system
 $oidstopush=array();
 foreach($localoids as $oid)
 {
  if(!array_key_exists($oid,$r))
  {// this local oid is not found in the renote list
   $oidstopush[]=$oid;      
  }
 }
 
 $logstring.='local oids to push: '.count($oidstopush).'; ';
 
  // convert the "push" list of OID's into asset records
 $assetstopush=array();
 foreach($oidstopush as $oid)
 {
  if(count($assetstopush)>=$pushlimit){break;}
  if($a=$asset->getAssetByOID($oid))
  {
    $assetstopush[]=$a;
  }
 }

 $logstring.='local assets to push: '.count($assetstopush).'; ';
 
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

 $logstring.='remote oids to drop: '.count($oidstodrop).'; ';

 if(count($assetstopush)>0 || count($oidstodrop)>0)
 {
  $pushsummary='';
  
  $body=array('identifier'=>$peer['identifier'],'adds'=>array(),'drops'=>$oidstodrop);

  $assetidkeyedassets=array(); 
  foreach($assetstopush as $a)
  {
   $assetidkeyedassets[$a['assetid']][]=array('id'=>$a['id'],'assetid'=>$a['assetid'],'filename'=>$a['filename'],'localpath'=>$a['localpath'],'uri'=>$a['uri'],'orientationViewCode'=>$a['orientationViewCode'],'colorModeCode'=>$a['colorModeCode'],'assetHeight'=>$a['assetHeight'],'assetWidth'=>$a['assetWidth'],'dimensionUOM'=>$a['dimensionUOM'],'background'=>$a['background'],'fileType'=>$a['fileType'],'createdDate'=>$a['createdDate'],'public'=>$a['public'],'approved'=>$a['approved'],'description'=>$a['description'],'oid'=>$a['oid'],'fileHashMD5'=>$a['fileHashMD5'],'filesize'=>$a['filesize'],'resolution'=>$a['resolution'],'languagecode'=>$a['languagecode'],'assetlabel'=>$a['assetlabel']);
  
   $pushsummary.=$a['assetid'].', ';
  }
 
  foreach($assetidkeyedassets as $assetid=>$assetrecords)
  {    
   $body['adds'][]=array('assetid'=>$assetid,'records'=>$assetrecords);
  }
 
  $signature = hash_hmac('SHA256', json_encode($body), $peer['sharedsecret'],false);
  $body['signature']=$signature;
  
  $curl = curl_init($uri);
  curl_setopt($curl, CURLOPT_URL, $uri);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $headers = array("Accept: application/json","Content-Type: application/json",);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
  $resp = curl_exec($curl);
  curl_close($curl);
  $runtime=time()-$starttime;
  $logs->logSystemEvent('replication', 0, 'pushed/dropped '.count($assetstopush).'/'.count($oidstodrop).' to '.$peer['description'].' in '.$runtime.' seconds. Pushed:'.$pushsummary.'. '.$logstring);
 }
}
?>