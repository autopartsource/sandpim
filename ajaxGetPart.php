<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']))
{
 $part=$pim->getPart($_GET['partnumber']);
 echo json_encode($part);
}?>
