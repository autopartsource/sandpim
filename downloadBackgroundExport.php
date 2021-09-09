<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'downloadBackgroundExport.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
$userid=0; if(array_key_exists('userid',$_SESSION)){$userid=$_SESSION['userid'];}

$logs = new logs;

$job = $pim->getBackgroundjobByToken($_GET['token']);
if($job)
{
 // make sure job is complete
 if($job['status']=='complete')
 {
  if($contents=file_get_contents($job['outputfile']))
  {
   header('Content-Disposition: attachment; filename="'.$job['clientfilename'].'"');
   header('Content-Type: '.$job['contenttype']);
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
 
 if($job['status']=='started')
 {
  echo 'File export has been queued for processing, but is not complete. Check back (refresh this page) for download of '.$job['clientfilename'];      
 }

 if($job['status']=='running')
 {
  echo 'File export is running. Check back (refresh this page) for download of '.$job['clientfilename'];      
 }
 
 if($job['status']=='failed')
 {
  echo 'An error was encountered while processing '.$job['clientfilename'];      
 }
 
 echo '<br/><br/><a href="./backgroundJobs.php">Back to background export jobs menu</a>';
}
else
{
 $logs->logSystemEvent('backgroundjob', $userid, 'exported file token ['.$_GET['token'].'] was not found for download');
 echo 'file not found'; 
}