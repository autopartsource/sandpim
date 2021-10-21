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
$postbody= json_decode($bodyraw,true);


    
$runtime=time()-$starttime;
$logs->logSystemEvent('assetacceptor', 0, print_r($postbody,true));   

//$logs->logSystemEvent('assetacceptor', 0, print_r($postbody,true).': Asset acceptor received '.count($assets).' asset metadata records in '.$runtime.' seconds');   

?>