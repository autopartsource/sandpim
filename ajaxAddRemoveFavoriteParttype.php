<?php
include_once('/var/www/html/class/pimClass.php');
include_once('/var/www/html/class/userClass.php');
include_once('/var/www/html/class/logsClass.php');
session_start();
$pim= new pim;
$user= new user;
$logs= new logs;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['parttypeid']) && isset($_GET['action']) && isset($_GET['parttypename']))
{
 $parttypeid=intval($_GET['parttypeid']);
 $parttypename= $_GET['parttypename'];

 switch($_GET['action'])
 {
  case 'add':
   $user->addFavoriteParttype($parttypeid,$parttypename);
   $logs->logSystemEvent('favoriteparttype',$_SESSION['userid'],'parttype:'.$parttypeid.' added to favorites');
  break;

  case 'remove':
  // $user->removeAppcategoryFromUser($userid,$appcategory,$permissionname);
  // $logs->logSystemEvent('favoriteparttype',$_SESSION['userid'],'parttype:'.$parttypeid.' removed to favorites');
  break;

  default:
  break;
 }
}

?>
