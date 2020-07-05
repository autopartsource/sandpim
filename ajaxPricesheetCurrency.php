<?php
include_once('./class/pricingClass.php');
include_once('./class/pcdbClass.php');
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
