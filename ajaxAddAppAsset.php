<?php
include_once('./class/pimClass.php');
session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['assetid']) && isset($_GET['representation']) && isset($_GET['sequence']))
{
 $result=array();
 $pim= new pim;
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
