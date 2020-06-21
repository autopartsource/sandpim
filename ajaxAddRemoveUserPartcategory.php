<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
$user= new user;
$logs= new logs;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['userid']) && isset($_GET['partcategory']) && isset($_GET['permissionname']) && isset($_GET['action']))
{
 $userid=intval($_GET['userid']);
 $partcategory=intval($_GET['partcategory']);
 $permissionname = $_GET['permissionname'];

 switch($_GET['action'])
 {
  case 'add':
   $user->addPartcategoryToUser($userid,$partcategory,$permissionname);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'permission:'.$permissionname.' granted to:'.$user->realNameOfUserid($userid).' on '.$pim->partCategoryName($partcategory));
  break;

  case 'remove':
   $user->removePartcategoryFromUser($userid,$partcategory,$permissionname);
   $logs->logSystemEvent('userchange',$_SESSION['userid'],'permission:'.$permissionname.' revoked from:'.$user->realNameOfUserid($userid).' on '.$pim->partCategoryName($partcategory));
  break;

  default:
  break;
 }
}

?>
