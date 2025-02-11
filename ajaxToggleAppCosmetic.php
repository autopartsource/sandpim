<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim=new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxToggleAppCosmetic.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 $userid=$_SESSION['userid'];
 $pim->toggleAppCosmetic(intval($_GET['appid']));
 $pim->logAppEvent(intval($_GET['appid']),$userid,'cosmetic toggled','');
}?>
