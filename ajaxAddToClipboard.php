<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);
if(isset($_SESSION['userid']) && isset($_GET['description']) && isset($_GET['objecttype']) && isset($_GET['objectkey']) && isset($_GET['objectdata']))
{
 $pim= new pim;
 $logs= new logs;
 $userid=intval($_SESSION['userid']);
 $description=base64_decode($_GET['description']);
 $objecttype=$_GET['objecttype'];
 $objectkey=$_GET['objectkey'];
 $objectdata=base64_decode($_GET['objectdata']);
 $id=$pim->addClipboardObject($userid,$description ,$objecttype, $objectkey, $objectdata);
 $logs->logSystemEvent('clipboard',$_SESSION['userid'],'added '.$objecttype.':'.$description);
 echo json_encode($id);
}
?>
