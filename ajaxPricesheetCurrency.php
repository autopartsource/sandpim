<?php
include_once('./class/pricingClass.php');
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxPricesheetCurrency.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$pricing=new pricing;
$pcdb=new pcdb;
$result=array('success'=>false,'currencycode'=>'','currencyname'=>'','pricetype'=>'','pricetypename'=>'not found');

if(isset($_SESSION['userid']) && isset($_GET['pricesheetnumber']) && strlen($_GET['pricesheetnumber'])<=20)
{
 if($pricesheet=$pricing->getPricesheet($_GET['pricesheetnumber']))
 {
  $result['currencycode']=$pricesheet['currency'];
  $result['pricetype']=$pricesheet['pricetype'];
  $result['pricetypename']=$pcdb->priceTypeDescription($pricesheet['pricetype']);
  $result['success']=true;
 }
}
echo json_encode($result);
?>
