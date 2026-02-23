<?php

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/replicationClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();
$replication=new replication();

$existinglocks=$pim->getLocksByType('PUSHAPPS');
if(count($existinglocks))
{
 $logs->logSystemEvent('replication', 0, 'pushApps found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
 exit; 
}
$mylockid=$pim->addLock('PUSHAPPS', 'pid:'. getmypid());

$peers=$replication->getPeers('%','app', 'secondary');

foreach($peers as $peer)
{
 if($peer['enabled']==0){continue;}
 $uri=$peer['uri'];
 $pushlimit=$peer['objectlimit'];
 

 $logstring='uri: '.$uri.'; ';

 $localoids=$pim->getAppOids();
if(count($localoids)==0)
{
 echo "refusing to push an empty local list\r\n";
 $logs->logSystemEvent('replication', 0, 'local app list is empty. No push completed (too risky).');
 continue;
}
 
 sort($localoids);
 $localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
 $localoidhash= md5($localoidliststring);
 
 $logstring.='localoids '.count($localoids). '; ';
 
 
 //ask server for a hash of its oids
 
 $curl = curl_init($uri.'?detail=hash');
 curl_setopt($curl, CURLOPT_URL, $uri.'?detail=hash');
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 $headers = array("Accept: application/json","Content-Type: application/json",);
 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
 $resp = curl_exec($curl);
 curl_close($curl);

 $responsedecoded= json_decode($resp, true); 
 
 if(!array_key_exists('hash',$responsedecoded))
 {
  $logs->logSystemEvent('replication', 0, 'unexpected response form '.$peer['description'].':'.$resp);    
  continue;
 }
 
 
 if($localoidhash == $responsedecoded['hash'])
 {
  // commented 11-11-2024 to bring the noise down   $logs->logSystemEvent('replication', 0, 'remote hash on '.$peer['description'].' equals local hash - no apps pushed');
  continue;
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
 
 if(!array_key_exists('oids',$responsedecoded))
 {
  $logs->logSystemEvent('replication', 0, 'unexpected response form '.$peer['description'].':'.$resp);
  continue;
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

 $logstring.='local apps to push: '.count($appstopush).'; ';
 
 
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

 $logstring.='remote oids to drop: '.count($oidstodrop).'; ';
 
 if(count($appstopush)>0 || count($oidstodrop)>0)
 {
  $body=array('identifier'=>$peer['identifier'],'adds'=>$appstopush,'drops'=>$oidstodrop);
  $signature = hash_hmac('SHA256', json_encode($body), $peer['sharedsecret'],false);
  $body['signature']=$signature;
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
  $logs->logSystemEvent('replication', 0, 'pushed/dropped '.count($appstopush).'/'.count($oidstodrop).' to '.$peer['description'].' in '.$runtime.' seconds. '.$logstring);
 }

}

$pim->removeLockById($mylockid);
?>