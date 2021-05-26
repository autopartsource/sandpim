<?php
include_once('./class/pimClass.php');
session_start();

if(isset($_SESSION['userid']))
{
 $result=array();
 $pim= new pim;
 $userid=intval($_SESSION['userid']);
 
 if(isset($_GET['id'])) {
     $id=$_GET['id'];
     $pim->deleteClipboardObject($userid, $id);
 }
 else {
     $pim->deleteClipboardObjects($userid);
 }
 
 $result['success']=true;
 echo json_encode($result);
}?>
