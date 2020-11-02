<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

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
