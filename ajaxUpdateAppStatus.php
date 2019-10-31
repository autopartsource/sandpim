<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['appid']) && isset($_GET['status']))
{
 $userid=$_SESSION['userid'];
 $newstatus=0;
 if($_GET['status']=='trash'){$newstatus=1;}
 if($_GET['status']=='hide'){$newstatus=2;}
 $pim->setAppStatus(intval($_GET['appid']),$newstatus);
 $pim->logHistoryEvent(intval($_GET['appid']),$userid,'status changed to '.$newstatus,'');
}?>
