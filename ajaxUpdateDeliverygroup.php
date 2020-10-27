<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
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
