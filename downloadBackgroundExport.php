<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();
$userid=0; if(array_key_exists('userid',$_SESSION)){$userid=$_SESSION['userid'];}

$pim = new pim;
$logs = new logs;

$job = $pim->getBackgroundjobByToken($_GET['token']);
if($job)
{
 if($contents=file_get_contents($job['outputfile']))
 {
  header('Content-Disposition: attachment; filename="'.$job['clientfilename'].'"');
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Length: ' . strlen($contents));
  header('Content-Transfer-Encoding: binary');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  echo $contents;
  $logs->logSystemEvent('backgroundjob', $userid, 'exported file was downloaded from job: '.$job['id']);
 }
 else
 {  // local file open failed
  echo 'file open failed'; 
  $logs->logSystemEvent('backgroundjob', $userid, 'exported file-open ['.$job['outputfile'].'] failed');
 }
}
else
{
 $logs->logSystemEvent('backgroundjob', $userid, 'exported file token ['.$_GET['token'].'] was not found for download');
 echo 'file not found'; 
}