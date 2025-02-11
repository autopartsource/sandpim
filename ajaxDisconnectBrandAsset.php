<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDisconnectBrandAsset.php - access denied (404 returned) to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
$asset=new asset();

if(isset($_SESSION['userid']) && isset($_GET['connectionid']) && isset($_GET['brandid']))
{
 $brandid=$_GET['brandid'];
 $userid=$_SESSION['userid'];
 $connectionid=intval($_GET['connectionid']);
 $asset->disconnectBrandFromAsset($brandid,$connectionid);
 $result=array('success'=>true);
 echo json_encode($result);
}
?>
