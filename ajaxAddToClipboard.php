<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddToClipboard.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['description']) && isset($_GET['objecttype']) && isset($_GET['objectkey']) && isset($_GET['objectdata']))
{
 $logs= new logs;
 $userid=intval($_SESSION['userid']);
 $description=base64_decode($_GET['description']);
 $objecttype=$_GET['objecttype'];
 $objectkey=$_GET['objectkey'];
 $objectdata=base64_decode($_GET['objectdata']);
 $id=$pim->addClipboardObject($userid,$description ,$objecttype, $objectkey, $objectdata);
 //$logs->logSystemEvent('clipboard',$_SESSION['userid'],'added '.$objecttype.':'.$description.' id:'.$id);
 echo json_encode($id);
}
?>