<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveReceiverLifecyclestatus.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$logs= new logs;
$pcdb = new pcdb;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['receiverprofileid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $receiverprofileid=intval($_GET['receiverprofileid']);
 $lifecyclestatus = ''; if(isset($_GET['lifecyclestatus'])){$lifecyclestatus=$_GET['lifecyclestatus'];}

 $receiverprofile=$pim->getReceiverprofileById($receiverprofileid);
         
 if($receiverprofile)
 {
  switch($_GET['action'])
  {
   case 'add':
    $result['id']=$pim->addLifecyclestatusToReceiverProfile($receiverprofileid, $lifecyclestatus);
    $result['lifecyclestatusdescription']=$pcdb->lifeCycleCodeDescription($lifecyclestatus);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Lifecycle Status code ['.$lifecyclestatus.'] added to receiver profile: '.$receiverprofile['name']);
   break;

   case 'remove':
    $recordid=intval($_GET['recordid']);
    $result['success']=$pim->removeLifecyclestatusFromReceiverProfile($recordid, $receiverprofileid);
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Lifecycle Status code removed from receiver profile: '.$receiverprofile['name']);
   break;

   default:
   break;
  }
 }
 echo json_encode($result);
}?>
