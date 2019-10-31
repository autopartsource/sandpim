<?php
include_once('/var/www/html/class/pimClass.php');
session_start();
$pim= new pim;
if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 $userid=$_SESSION['userid'];
 $pim->toggleAppCosmetic(intval($_GET['appid']));
 $pim->logHistoryEvent(intval($_GET['appid']),$userid,'cosmetic toggled by drag','');
}?>
