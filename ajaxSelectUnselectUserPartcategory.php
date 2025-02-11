<?php
include_once('./class/userClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxSelectUnselectUserPartcategory.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$user= new user;

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['partcategory']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $partcategory=intval($_GET['partcategory']);

 switch($_GET['action'])
 {
  case 'select':
   $user->userSelectPartcategory($userid,$partcategory);
  break;

  case 'unselect':
   $user->userUnselectPartcategory($userid,$partcategory);
  break;

  default:
  break;
 }
}

?>
