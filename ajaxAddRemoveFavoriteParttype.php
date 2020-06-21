<?php
include_once('./class/pimClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
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
   $user->removeFavoriteParttype($parttypeid,$parttypename);
   $logs->logSystemEvent('favoriteparttype',$_SESSION['userid'],'parttype:'.$parttypeid.' removed from favorites');
  break;

  default:
  break;
 }
}

?>
