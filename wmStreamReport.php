<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/walmartClass.php');

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'wmStreamReport.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$configGet = new configGet();
$WMclientid=$configGet->getConfigValue('WMclientid');
$WMsecret=$configGet->getConfigValue('WMsecret');
$WMconsumerid=$configGet->getConfigValue('WMconsumerid');
$WMconsumerchanneltype=$configGet->getConfigValue('WMconsumerchanneltype');
$wm=new walmart($WMclientid, $WMsecret, $WMconsumerid, $WMconsumerchanneltype);

$session=$wm->getSession(intval($_GET['sessionid']));
$localfeed=$wm->getFeed(intval($_GET['feedid']));

$wm->accesstoken=$session['accesstoken'];
$wm->correlationid=$session['correlationid'];
$wm->feedid=$localfeed['feedid'];
$wm->feedtype=$localfeed['type'];

$wm->apiStreamReport();

$filename='Walmart_feed_report_'.$localfeed['feedid'].'_'.date('Y-m-d').'.zip';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/zip');
 //header('Content-Type: application/octet-stream');
 header('Content-Length: ' . strlen($wm->reportcontent));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $wm->reportcontent;

?>