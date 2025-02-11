<?php
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/replicationClass.php');
include_once(__DIR__.'/class/pricingClass.php');
include_once(__DIR__.'/class/interchangeClass.php');
include_once(__DIR__.'/class/packagingClass.php');
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/logsClass.php');

$starttime=time();

$pim = new pim();
$replication=new replication();
$pricing=new pricing();
$interchange = new interchange();
$packaging = new packaging();
$asset = new asset();
$logs=new logs();

$localparts=$pim->getParts('', 'startswith', 'any', 'any', 'any', 'any', 999999);
if(count($localparts)==0)
{
 echo "refusing to push an empty local list\r\n";
 $logs->logSystemEvent('replication', 0, 'local part list is empty. No push completed (too risky).');
 exit;
}


$localoids=array(); foreach($localparts as $localpart){$localoids[]=$localpart['oid'];}
sort($localoids);
$localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
$localoidhash= md5($localoidliststring);

$peers=$replication->getPeers('%','part', 'secondary');

foreach($peers as $peer)
{
 if($peer['enabled']==0){continue;}
 $uri=$peer['uri'];
 $pushlimit=$peer['objectlimit'];
 
 $logstring='uri: '.$uri.'; localoids: '.count($localoids). '; ';
 
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
  $logs->logSystemEvent('partpusher', 0, 'unexpected response in pushParts form '.$peer['description'].':'.$resp);    
  continue; // iterate to next peer
 }
 

 if($localoidhash == $responsedecoded['hash'])
 {
// commented 11-11-2024 to bring the noise down  $logs->logSystemEvent('replication', 0, 'remote hash on '.$peer['description'].' equals local hash - no parts pushed');
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
  $logs->logSystemEvent('replication', 0, 'unexpected response in pushParts form '.$peer['description'].':'.$resp);    
  continue; // iterate to next peer
 }
 
 // we now have an array of oids from the remote (secondary) system
 $r=array(); foreach($responsedecoded['oids'] as $oid){$r[$oid]='';}
 $l=array(); foreach($localoids as $oid){$l[$oid]='';}
 
 $logstring.='local distinct oids: '.count($l).'; ';
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

  // convert the "push" list of OID's into part objecta
 $partstopush=array();
 foreach($oidstopush as $oid)
 {
  if($p=$pim->getPartByOID($oid))
  {
   //get the part_to records and add them to the $p object
   $descriptions=$pim->getPartDescriptions($p['partnumber']);
   $attributes=$pim->getPartAttributes($p['partnumber']);
   $prices=$pricing->getPricesByPartnumber($p['partnumber']);
   $packages=$packaging->getPackagesByPartnumber($p['partnumber']);
   $interchanges=$interchange->getInterchangeByPartnumber($p['partnumber']);
   $assetconnections=$asset->getAssetsConnectedToPart($p['partnumber']);
   $p['descriptions']=$descriptions;
   $p['attributes']=$attributes;
   $p['prices']=$prices;
   $p['packages']=$packages;
   $p['interchanges']=$interchanges;
   $p['assetconnections']=$assetconnections;
   $partstopush[]=$p;
  }
  if(count($partstopush)>= $pushlimit){break;} // limit pushlias
  
 }

 $logstring.='local parts to push: '.count($partstopush).'; ';

 // compare sets of oids to determine what's extra in remote system 
 // don't bother converting them to real partnumbers - they may not exist locally
 $oidstodrop=array();
 foreach($responsedecoded['oids'] as $oid)
 {
  if(!array_key_exists($oid,$l))
  {// this remote oid is not found in the local list
   $oidstodrop[]=$oid;
  }
 }

 $logstring.='remote oids to drop: '.count($oidstodrop).'; ';

 if(count($partstopush)>0 || count($oidstodrop)>0)
 {
  $body=array('identifier'=>$peer['identifier'],'adds'=>array(),'drops'=>$oidstodrop);

  foreach($partstopush as $part)
  {    
   $body['adds'][]=$part;
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
  $logs->logSystemEvent('replication', 0, 'pushed/dropped '.count($partstopush).'/'.count($oidstodrop).' parts to '.$peer['description'].' in '.$runtime.' seconds. '.$logstring);
 }

}
?>