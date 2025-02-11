<?php
include_once('./class/pimClass.php');
include_once('./class/replicationClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$replication = new replication();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptApps.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$newappcount=0;  $droppedappcount=0;

if(array_key_exists('detail',$_GET))
{ // get local list of all app oid's
 $localoids=$pim->getAppOids();
 sort($localoids);
 $localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
 
 if($_GET['detail']=='hash')
 {
  $hash=md5($localoidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('replication', 0, 'gave hash ('.$hash.') of '.count($localoids).' local app oids to client '.$_SERVER['REMOTE_ADDR']);
 }
 else
 {
  echo json_encode(array('oids'=>$localoids));
  $logs->logSystemEvent('replication', 0, 'gave list of '.count($localoids).' local app oids to client '.$_SERVER['REMOTE_ADDR']);
 }
}

$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

 if(!array_key_exists('identifier',$body) || !array_key_exists('signature',$body))
 {
  $logs->logSystemEvent('replication', 0, 'invalid data (missing identifier or signature) from client '.$_SERVER['REMOTE_ADDR']);
  exit;
 }
 
 // lookup peer by its claimed identifier
 $peers=$replication->getPeers($body['identifier'],'app', 'primary');
 if(count($peers)==0)
 {
  $logs->logSystemEvent('replication', 0, 'unknown identifier ['.$body['identifier'].'] from client '.$_SERVER['REMOTE_ADDR']);  
  exit;
 }
 
 //test signature of payload
 $computedsignature = hash_hmac('SHA256', json_encode(array('identifier'=>$body['identifier'],'adds'=>$body['adds'],'drops'=>$body['drops'])), $peers[0]['sharedsecret'],false);
 if($body['signature']!=$computedsignature)
 {
  $logs->logSystemEvent('replication', 0, 'invalid signature on payload - no adds/drops accepted from peer identified by: '.$body['identifier']);
  exit;
 }
 
 if(array_key_exists('drops',$body))
 { // drop list is oid's (not appid's)
  foreach ($body['drops'] as $oid)
  {
   $appids=$pim->deleteAppByOid($oid);
   foreach($appids as $appid)
   {// possible (but unlikely) that multiple apps could have had the same oid - delete them all
    $pim->logAppEvent($appid, 0, 'app deleted by replication API', '');
   }
   $droppedappcount++;
  }
 }
 
 if(array_key_exists('adds',$body))
 {
  foreach ($body['adds'] as $a)
  {
   $newappid=$pim->newApp($a['basevehicleid'], $a['parttypeid'], $a['positionid'], $a['quantityperapp'], $a['partnumber'], $a['cosmetic'], $a['attributes'],$a['oid']);
   if($newappid)
   {
    $pim->logAppEvent($newappid, 0, 'app created by replication API', '');
    $newappcount++;
   }
   else
   {
    $logs->logSystemEvent('replication', 0, 'App creation failed');          
   }
  }
 }
}
    
$runtime=time()-$starttime;
if($newappcount || $runtime>10)
{
 $logs->logSystemEvent('replication', 0, 'Added '.$newappcount.' apps, dropped '.$droppedappcount.' in '.$runtime.' seconds');   
}
?>