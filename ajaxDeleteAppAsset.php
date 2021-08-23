<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeleteAppAsset.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['id']))
{
 $result=array();
 $appid=intval($_GET['appid']);
 $id=intval($_GET['id']);
 $userid=$_SESSION['userid'];
 $pim->deleteAppAsset($appid, $id);
 $result['oid']=$pim->getOIDofApp($appid);
 $pim->logAppEvent($appid,$userid,'Fitment asset deleted',$result['oid']);
 $result['success']=true;
 echo json_encode($result);
}?>
