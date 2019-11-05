<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/logsClass.php');
session_start();
$pim= new pim;
$user= new user;
$logs= new logs;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['appcategory']) && isset($_GET['permissionname']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $appcategory=intval($_GET['appcategory']);
 $permissionname = $_GET['permissionname'];

 switch($_GET['action'])
 {
  case 'add':
   $user->addAppcategoryToUser($userid,$appcategory,$permissionname);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'permission:'.$permissionname.' granted to:'.$user->realNameOfUserid($userid).' on '.$pim->appCategoryName($appcategory));
  break;

  case 'remove':
   $user->removeAppcategoryFromUser($userid,$appcategory,$permissionname);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'permission:'.$permissionname.' revoked from:'.$user->realNameOfUserid($userid).' on '.$pim->appCategoryName($appcategory));
  break;

  default:
  break;
 }
}

?>
