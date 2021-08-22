<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveDeliverygroupPartcategory.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$logs= new logs;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['partcategoryid']) && isset($_GET['deliverygroupid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $partcategoryid=intval($_GET['partcategoryid']);
 $deliverygroupid = intval($_GET['deliverygroupid']);

 $partcategory=$pim->getPartCategory($partcategoryid);
 $deliverygroup=$pim->getDeliverygroup($deliverygroupid);
         
 if($deliverygroup && $partcategory)
 {
  switch($_GET['action'])
  {
   case 'add':
    $result['id']=$pim->addPartcategoryToDeliverygroup($deliverygroupid, $partcategoryid);
    $result['success']=true;
    $logs->logSystemEvent('deliverygroupchange',$_SESSION['userid'],'Partcategory: '.$partcategory['name'].' added to deliverygroup: '.$deliverygroup['description']);
   break;

   case 'remove':
    $pim->removePartcategoryFromDeliverygroup($deliverygroupid, $partcategoryid);
    $result['success']=true;
    $logs->logSystemEvent('deliverygroupchange',$_SESSION['userid'],'Partcategory: '.$partcategory['name'].' removed from deliverygroup: '.$deliverygroup['description']);
   break;

   default:
   break;
  }
 }
 echo json_encode($result);
}?>
