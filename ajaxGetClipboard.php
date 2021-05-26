<?php
include_once('./class/pimClass.php');
session_start();

if(isset($_SESSION['userid']))
{
 $pim= new pim;
 $userid=intval($_SESSION['userid']);
 $objecttype='%';
 if(isset($_GET['objecttype'])){$objecttype=$_GET['objecttype'];}
 $clipboard=$pim->getClipboard($userid, $objecttype);
 echo json_encode($clipboard);
}?>
