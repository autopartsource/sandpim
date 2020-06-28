<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
session_start();
$pim= new pim;
$interchange= new interchange;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);
$success=false; $oid='';

if(isset($_SESSION['userid']) && isset($_GET['id']) && isset($_GET['partnumber']))
{

 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $id=intval($_GET['id']);
 $interchangerecord=$interchange->getInterchangeById($id);
 if($interchangerecord)
 {
  if($interchangerecord['partnumber']==$partnumber)
  {
   if($interchange->deleteInterchangeById($id))
   {
    $oid=$pim->updatePartOID($partnumber);
    $eventtext='competitor interchange ['.$interchangerecord['brandAAIAID'].':'.$interchangerecord['competitorpartnumber'].'] was deleted';  
    $success=true;
    $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
   }
  }
  $result=array('success'=>$success,'oid'=>$oid);
 }
 
 echo json_encode($result);
}
?>
