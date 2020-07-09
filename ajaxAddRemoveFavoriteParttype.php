<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
$pcdb=new pcdb;
$user= new user;
$logs= new logs;

if(isset($_SESSION['userid']) && isset($_GET['parttypeid']) && isset($_GET['action']))
{
 $parttypeid=intval($_GET['parttypeid']);
 $parttypename=$pcdb->parttypeName($parttypeid);

 switch($_GET['action'])
 {
  case 'add':
   $pim->addFavoriteParttype($parttypeid,$parttypename);
   $logs->logSystemEvent('favoriteparttype',$_SESSION['userid'],'parttype '.$parttypeid.' ('.$parttypename.') added to favorites');
  break;

  case 'remove':
   $pim->removeFavoriteParttype($parttypeid);
   $logs->logSystemEvent('favoriteparttype',$_SESSION['userid'],'parttype '.$parttypeid.' ('.$parttypename.') removed from favorites');
  break;

  default:
  break;
 }
}
?>
