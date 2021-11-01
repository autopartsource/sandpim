<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptApps - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$newappcount=0;  $droppedappcount=0;

if(isset($_GET['detail']))
{ // get local list of all app oid's
 $localoids=$pim->getAppOids();
 sort($localoids);
 $localoidliststring=''; foreach($localoids as $localoid){$localoidliststring.=$localoid;}
 
 if($_GET['detail']=='hash')
 {
  $hash=md5($localoidliststring);
  echo json_encode(array('hash'=> $hash));
  $logs->logSystemEvent('appacceptor', 0, 'gave hash ('.$hash.') of '.count($localoids).' local app oids to client '.$_SERVER['REMOTE_ADDR']);
 }
 else
 {
  echo json_encode(array('oids'=>$localoids));
  $logs->logSystemEvent('appacceptor', 0, 'gave list of '.count($localoids).' local app oids to client '.$_SERVER['REMOTE_ADDR']);
 }
}

$bodyraw=file_get_contents('php://input');

if(strlen($bodyraw)>0)
{
 $body=json_decode($bodyraw,true);

 if(isset($body['drops']))
 { // drop list is oid's (not appid's)
  foreach ($body['drops'] as $oid)
  {
   $appids=$pim->deleteAppByOid($oid);
   foreach($appids as $appid)
   {// possible (but unlikely) that multiple apps could have had the same oid - delete them all
    $pim->logAppEvent($appid, 0, 'app deleted by acceptApps API', '');
   }
   $droppedappcount++;
  }
 } 
 
 if(isset($body['adds']))
 {
  foreach ($body['adds'] as $a)
  {

      
  }
 }
}
    
$runtime=time()-$starttime;
if($newappcount || $runtime>10)
{
 $logs->logSystemEvent('appacceptor', 0, 'App acceptor added '.$newappcount.', dropped '.$droppedappcount.' apps in '.$runtime.' seconds');   
}
?>