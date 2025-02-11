<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveFavoriteMake.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$vcdb=new vcdb;
$logs= new logs;

if(isset($_SESSION['userid']) && isset($_GET['makeid']) && isset($_GET['action']))
{
 $makeid=intval($_GET['makeid']);
 $makename=$vcdb->makeName($makeid);

 switch($_GET['action'])
 {
  case 'add':
   $pim->addFavoriteMake($makeid,$makename);
   $logs->logSystemEvent('favoritemake',$_SESSION['userid'],'make '.$makeid.' ('.$makename.') added to favorites');
  break;

  case 'remove':
   $pim->removeFavoriteMake($makeid);
   $logs->logSystemEvent('favoritemake',$_SESSION['userid'],'make '.$makeid.' ('.$makename.') removed from favorites');
  break;

  default:
  break;
 }
}
?>
