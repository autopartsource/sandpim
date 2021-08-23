<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateAppStatus.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['status']))
{
 $userid=$_SESSION['userid'];
 $newstatus=0;
 if($_GET['status']=='trash'){$newstatus=1;}
 if($_GET['status']=='hide'){$newstatus=2;}
 $pim->setAppStatus(intval($_GET['appid']),$newstatus);
 $pim->logAppEvent(intval($_GET['appid']),$userid,'status changed to '.$newstatus,'');
}?>
