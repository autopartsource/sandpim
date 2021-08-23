<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxDeleteClipboard.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']))
{
 $result=array();
 $userid=intval($_SESSION['userid']);
 
 if(isset($_GET['id']))
 {
  $id=$_GET['id'];
  $pim->deleteClipboardObject($userid, $id);
 }
 else
 {
  $pim->deleteClipboardObjects($userid);
 }
 
 $result['success']=true;
 echo json_encode($result);
}?>
