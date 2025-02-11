<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateDeliverygroup.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$logs=new logs;

if(isset($_SESSION['userid']) && isset($_GET['deliverygroupid']) && isset($_GET['elementid']) && isset($_GET['value']))
{
 $deliverygroupid=intval($_GET['deliverygroupid']);
 $userid=$_SESSION['userid'];
 
 switch($_GET['elementid'])
 {
  case 'description':
      $deliverygroup=$pim->getDeliverygroup($deliverygroupid);
      $pim->setDeliverygroupDescription($deliverygroupid,$_GET['value']);
      $logs->logSystemEvent('deliverygroupchange',$_SESSION['userid'],'Description changed from ['.$deliverygroup['description'].'] to ['.$_GET['value'].']');

  break;


  default:
   break;
 }
}?>
