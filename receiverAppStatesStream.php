<?php
include_once('./class/pimClass.php');
//include_once('./class/vcdbClass.php');
//include_once('./class/pcdbClass.php');
//include_once('./class/qdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');

$navCategory = 'export';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'receiverAppStatesStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

//$vcdb=new vcdb();
//$pcdb=new pcdb();
$user=new user();
//$qdb=new qdb();
$logs=new logs();

$receiverprofileid=intval($_GET['receiverprofile']);
$exportformat='raw-apps'; if(in_array($_GET['format'],['raw-apps','decoded-apps','meta'])){$exportformat=$_GET['format'];}
$clientfilename='export_'.$receiverprofileid.'_'.$exportformat.'_'.date('Y-m-d').'.txt';
$filecontent='';

$appstates=$pim->getReceiverAppStates($receiverprofileid);

$explanation='';// 'Exported on: '.$export['datetimeexported'].' for receiver Profile: '.$pim->receiverprofileName($export['receiverprofileid']).' with notes: '.$export['notes'];

if($exportformat=='meta')
{
 $filecontent="App ID\tState\t".$explanation."\r\n";
 foreach($appstates as $appstate)
 {
  $filecontent.=$appstate['applicationid']."\t".$appstate['oid']."\r\n";
 }
}
 
header('Content-Disposition: attachment; filename="'.$clientfilename.'"');
header('Content-Type: text');
header('Content-Length: ' . strlen($filecontent));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo $filecontent;

$logs->logSystemEvent('Export', $_SESSION['userid'], 'Receiver ['.$pim->receiverprofileName($receiverprofileid).'] app-state data exported by:'.$_SERVER['REMOTE_ADDR']);