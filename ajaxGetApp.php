<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxGetApp.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 $app=$pim->getApp(intval($_GET['appid']));
 echo json_encode($app);
}?>
