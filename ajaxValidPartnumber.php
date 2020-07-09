<?php
include_once('./class/pimClass.php');
session_start();
$pim=new pim;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && strlen($_GET['partnumber'])<=20)
{
 echo json_encode(array('value'=>$pim->validPart($_GET['partnumber'])));
}?>