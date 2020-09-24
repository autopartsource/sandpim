<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
$pcdb=new pcdb;
$logs= new logs;

if(isset($_SESSION['userid']) && isset($_GET['positionid']) && isset($_GET['action']))
{
 $positionid=intval($_GET['positionid']);
 $positionname=$pcdb->positionName($positionid);

 switch($_GET['action'])
 {
  case 'add':
   $pim->addFavoritePosition($positionid, $positionname);
   
   $logs->logSystemEvent('favoriteposition',$_SESSION['userid'],'position '.$positionid.' ('.$positionname.') added to favorites');
  break;

  case 'remove':
   $pim->removeFavoritePosition($positionid);
   $logs->logSystemEvent('favoriteposition',$_SESSION['userid'],'position '.$positionid.' ('.$positionname.') removed from favorites');
  break;

  default:
  break;
 }
}
?>