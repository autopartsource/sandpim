<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
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
