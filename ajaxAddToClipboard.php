<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);
if(isset($_SESSION['userid']) && isset($_GET['objecttype']) && isset($_GET['objectkey']) && isset($_GET['objectdata']))
{
 $pim= new pim;
 $logs= new logs;
 $userid=intval($_SESSION['userid']);
 $objecttype=$_GET['objecttype'];
 $objectkey=$_GET['objectkey'];
 $objectdata=$_GET['objectdata'];
 $id=$pim->addClipboardObject($userid, $objecttype, $objectkey, $objectdata);
 $logs->logSystemEvent('clipboard',$_SESSION['userid'],'added '.$objecttype.':'.$objectkey);
 echo json_encode($id);
}
?>
