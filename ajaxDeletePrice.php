<?php
include_once('./class/pimClass.php');
include_once('./class/pricingClass.php');
session_start();
$pim= new pim;
$pricing= new pricing;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $pricerecord=$pricing->getPriceById($id);
 if($pricerecord)
 {
  if($pricerecord['partnumber']==$partnumber)
  {
   if($pricing->deletePriceById($id))
   {
    $oid=$pim->updatePartOID($partnumber);
    $eventtext='price ['.$pricerecord['niceprice'].'] was deleted';  
    $success=true;
    $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 echo json_encode($result);
}
?>
