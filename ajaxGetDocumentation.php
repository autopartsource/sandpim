<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxGetDocumentation.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$result='';

if(isset($_SESSION['userid']) && isset($_GET['path']))
{
 $path= base64_decode($_GET['path']);
 $records=$pim->getDocumentationText($path,'EN');
 foreach($records as $record)
 {
     $result.=$record['doctext'];
 }
 echo $result;
}?>
