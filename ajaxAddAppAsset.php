<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddAppAsset.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['assetid']) && isset($_GET['representation']) && isset($_GET['sequence']))
{
 $result=array();
 $appid=intval($_GET['appid']);
 $assetid=$_GET['assetid'];
 $representaion=$_GET['representation'];
 $sequence=intval($_GET['sequence']);
 $userid=$_SESSION['userid'];
 $id=$pim->addAssetToApp($appid, $assetid, $representaion, $sequence, 0);
 $result['oid']=$pim->getOIDofApp($appid);
 $pim->logAppEvent($appid,$userid,'Fitment asset ['.$assetid.'] added',$result['oid']);
 $result['success']=true; $result['id']=$id; 
 echo json_encode($result);
}?>
