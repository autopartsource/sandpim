<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
include_once('./class/logsClass.php');

$pim=new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddPrice.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$pricing=new pricing;

$result=array('success'=>false,'id'=>0,'oid'=>'','niceprice'=>'???');
$id=false;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['amount']) && is_numeric($_GET['amount']) && isset($_GET['pricetype']) && strlen($_GET['pricetype'])<=4 && isset($_GET['pricesheetnumber']) && isset($_GET['priceuom']) )
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $pricesheetnumber=$_GET['pricesheetnumber'];
 $amount=$_GET['amount'];
 $currency=$_GET['currency'];
 $priceuom=$_GET['priceuom'];
 $pricetype=$_GET['pricetype'];
 
 if($pim->validPart($partnumber))
 {
  if($pricesheet=$pricing->getPricesheet($pricesheetnumber))
  {  
   // these come from the price sheet
   $effectivedate=$pricesheet['effectivedate'];
   $expirationdate=$pricesheet['expirationdate'];
      
   if($id=$pricing->addPrice($partnumber, $pricesheetnumber, $amount, $currency, $priceuom, $pricetype, $effectivedate, $expirationdate))
   {
    $oid=$pim->updatePartOID($partnumber);
    $price=$pricing->getPriceById($id);
    $niceprice=$price['niceprice'];

    $eventtext='price ['.$niceprice.'] was added';
    $success=true;
    $pim->logPartEvent($partnumber, $userid, $eventtext, $oid);
   }
   $result['success']=$success; $result['id']=$id; $result['oid']=$oid; $result['niceprice']=$niceprice;
  }
 }
 echo json_encode($result);
}
?>