<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
$logs = new logs;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveUserNavelement.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$user= new user;

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['navid']) && $pim->validNavelement($_GET['navid']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $navid=$_GET['navid'];

 switch($_GET['action'])
 {
  case 'add':
   $pim->addUserNavelement($userid, $navid);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'Nav Element:'.$navid.' granted to:'.$user->realNameOfUserid($userid));
  break;

  case 'remove':
   $pim->removeUserNavelement($userid, $navid);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'Nav Element:'.$navid.' revoked from:'.$user->realNameOfUserid($userid));
  break;

  default:
  break;
 }
}?>
