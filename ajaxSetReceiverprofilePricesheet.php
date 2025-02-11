<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
$logs = new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'ajaxSetReceiverprofilePricesheet.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404);
 exit;
}

session_start();
$pricing=new pricing;

$result=array('success'=>false);

if(isset($_SESSION['userid']) && isset($_GET['receiverprofileid']) && isset($_GET['pricesheetnumber']) && strlen($_GET['pricesheetnumber'])<=20)
{
 $receiverprofileid=intval($_GET['receiverprofileid']);
 $receiverprofile=$pim->getReceiverprofileById($receiverprofileid); 
 
 $pricesheetnumber=false; $pricesheetdescription='none';
 if($pricesheet=$pricing->getPricesheet($_GET['pricesheetnumber']))
 {
  $pricesheetnumber=$pricesheet['number'];
  $pricesheetdescription=$pricesheet['description'];
 }
 
 if($receiverprofile)
 {
  $pim->setReceiverprofilePricesheet($receiverprofileid, $pricesheetnumber);
  $logs->logSystemEvent('receiverprofilechange',$_SESSION['userid'],'Receiver Profile ['.$receiverprofile['name'].'] pricesheet set to ['.$pricesheetdescription.']');
 }
}
echo json_encode($result);
?>
