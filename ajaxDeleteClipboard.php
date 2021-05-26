<?php
include_once('./class/pimClass.php');
session_start();

if(isset($_SESSION['userid']) && isset($_GET['id']))
{
 $result=array();
 $pim= new pim;
 $userid=intval($_SESSION['userid']);
 $id=intval($_GET['id']);
 $pim->deleteClipboardObject($userid, $id);
 $result['success']=true;
 echo json_encode($result);
}?>
