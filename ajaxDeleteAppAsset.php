<?php
include_once('./class/pimClass.php');
session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['id']))
{
 $result=array();
 $pim= new pim;
 $appid=intval($_GET['appid']);
 $id=intval($_GET['id']);
 $userid=$_SESSION['userid'];
 $pim->deleteAppAsset($appid, $id);
 $result['oid']=$pim->getOIDofApp($appid);
 $pim->logAppEvent($appid,$userid,'Fitment asset deleted',$result['oid']);
 $result['success']=true;
 echo json_encode($result);
}?>
