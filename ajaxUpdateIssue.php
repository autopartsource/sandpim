<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateIssue.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

if(isset($_SESSION['userid']) && isset($_GET['issueid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $issueid=intval($_GET['issueid']);
 $userid=$_SESSION['userid'];
 $issue=$pim->getIssueById($issueid);

 switch($_GET['elementid'])
 {
  case 'status':
   $pim->updateIssueStatus($issueid,intval($_GET['value']));
   break;

  case 'notes':
   $pim->updateIssueNotes($issueid,$_GET['value']);
  break;

  case 'snoozedays':
      $pim->snoozeIssue($issueid, intval($_GET['value']));
  break;

  default:
  break;
 }

}?>
