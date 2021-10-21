<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$starttime=time();

$pim = new pim();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'acceptAssets - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


$asset=new asset();

$bodyraw=file_get_contents('php://input');
$assetrecords= json_decode($bodyraw,true);

$assetid='not found';
if(count($assetrecords))
{
 foreach ($assetrecords as $assetrecord)
 {
  if(array_key_exists('asset', $assetrecord) && array_key_exists('connections', $assetrecord))
  {
   // see if the assets already exists here
      
      $assetid=$assetrecord['asset']['assetid'];
      
  }
 }
}



    
$runtime=time()-$starttime;
$logs->logSystemEvent('assetacceptor', 0, $assetid);   

//$logs->logSystemEvent('assetacceptor', 0, print_r($postbody,true).': Asset acceptor received '.count($assets).' asset metadata records in '.$runtime.' seconds');   

?>