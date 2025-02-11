<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveReceiverDeliverygroup.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$logs= new logs;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['receiverprofileid']) && isset($_GET['deliverygroupid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $receiverprofileid=intval($_GET['receiverprofileid']);
 $deliverygroupid = intval($_GET['deliverygroupid']);

 $receiverprofile=$pim->getReceiverprofileById($receiverprofileid);
 $deliverygroup=$pim->getDeliverygroup($deliverygroupid);
         
 if($receiverprofile && $deliverygroup)
 {
  switch($_GET['action'])
  {
   case 'add':
    $result['id']=$pim->addDeliverygroupToReceiverProfile($receiverprofileid, $deliverygroupid);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Delivery Group: '.$deliverygroup['description'].' added to receiver profile: '.$receiverprofile['name'].'('.$result['id'].')');
   break;

   case 'remove':
    $pim->removeDeliverygroupFromReceiverProfile($receiverprofileid, $deliverygroupid);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Delivery Group: '.$deliverygroup['description'].' removed from receiver profile: '.$receiverprofile['name']);
   break;

   default:
   break;
  }
 }
 echo json_encode($result);
}?>
