<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperAPIclass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'streamFilegrain.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();
$sandpiper = new sandpiper();


$grain=$sandpiper->getFilegrainByUUID($_GET['uuid'],true);
if($grain)
{
 $logs->logSystemEvent('sandpiper', $_SESSION['userid'], 'downloaded grain');

 $filename=$grain['source'];
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/octet-stream');
 header('Content-Length: ' . strlen($grain['payload']));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $grain['payload'];
}?>