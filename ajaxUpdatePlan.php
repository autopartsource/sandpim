<?php
include_once('./class/pimClass.php');
include_once('./class/sandpiperPrimaryClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdatePlan.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
// used for setting scalar values of plans ('metadata','statuson','primaryapprovedon','secondaryapprovedon','description')
// not used for adding/removing one-to-many things like subscriptions.
 
if(isset($_SESSION['userid']) && isset($_GET['planid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $logs = new logs;
 $sp = new sandpiperPrimary();
 $userid=$_SESSION['userid'];
 $planid=intval($_GET['planid']);
 $plan=$sp->getPlanById($planid);
 $response='';
 $datetimenow=date('Y-m-d H:i:s');
 
 switch($_GET['elementid'])
 {
  case 'planmetadata':
   if($plan['planmetadata']!=base64_decode($_GET['value']))
   {
    $sp->updatePlanMetadata($planid, base64_decode($_GET['value']));
    $logs->logSystemEvent('sandpiper',$_SESSION['userid'], 'metadata for plan '.$planid.' updated to: '.base64_decode($_GET['value']));
   }
    break;

  case 'planstatuson':
    $sp->updatePlanStatusOn($planid, $datetimenow);  
    $logs->logSystemEvent('sandpiper',$_SESSION['userid'], 'status-on timestamp for plan '.$planid.' updated to: '.$datetimenow);
    $response=$datetimenow;
    break;

  case 'primaryapprovedon':
    $sp->updatePlanPrimaryApprovedOn($planid, $datetimenow);
    $logs->logSystemEvent('sandpiper',$_SESSION['userid'], 'primary approved-on timestamp for plan '.$planid.' updated to: '.$datetimenow);
    $response=$datetimenow;
    break;

  case 'secondaryapprovedon':
    $sp->updatePlanSecondaryApprovedOn($planid, $datetimenow);  
    $logs->logSystemEvent('sandpiper',$_SESSION['userid'], 'secondary approved-on timestamp for plan '.$planid.' updated to: '.$datetimenow);
    $response=$datetimenow;
    break;

  case 'description':
   if($plan['description']!=base64_decode($_GET['value']))
   {
     $sp->updatePlanDescription($planid,base64_decode($_GET['value']));  
     $logs->logSystemEvent('sandpiper',$_SESSION['userid'], 'description for plan '.$planid.' updated to: '.base64_decode($_GET['value']));
     break;
   }

  default:
   $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'ajaxUpdatePlan.php: unhandled plan element ('.$_GET['elementid'].') from client '.$_SERVER['REMOTE_ADDR']);
   break;
 }
 
 echo $response;
}?>
