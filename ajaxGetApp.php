<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['appid']))
{
 $app=$pim->getApp(intval($_GET['appid']));
 echo json_encode($app);
}?>
