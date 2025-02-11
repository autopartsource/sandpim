<?php
/* stream a txt file of crlf-delimited list of partnumbers in the export of a specific reciver profile
 * for use in previewing the list of items in a submission.
 * 
 * 
 */
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim = new pim;
$logs = new logs;

session_start();

if(isset($_SESSION['userid']))
{
 $userid=$_SESSION['userid'];
}
else
{// no session exists - user not logged in
 $logs->logSystemEvent('accesscontrol',0, 'exportPartListStream.php - access denied to unauthenticated user from '.$_SERVER['REMOTE_ADDR']);
 exit; 
}

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$userid, 'exportPartListStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$partnumberkeyedlist=array();
$partnumberlist=array();

$profile=$pim->getReceiverprofileById(intval($_GET['receiverprofile']));

if($profile)
{
 $partcategories=$pim->getReceiverprofilePartcategories($profile['id']);
 $lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($profile['id']);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);
 foreach($partnumbers as $partnumber)
 { 
  if(!array_key_exists($partnumber, $partnumberkeyedlist))
  {
   $partnumberkeyedlist[$partnumber]='';
  }   
 }  
 

 ksort($partnumberkeyedlist);
 foreach($partnumberkeyedlist as $partnumber=>$trash)
 {
  $partnumberlist[]=$partnumber;
 }
 
 $filecontents= implode("\r\n", $partnumberlist);

 $filename='parts_'.date('Y-m-d').'.txt';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/octet-stream');
 header('Content-Length: ' . strlen($filecontents));
 header('Connection: close');    
 echo $filecontents;
}
?>
