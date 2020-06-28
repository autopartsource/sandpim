<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
session_start();
$pim= new pim;
$interchange= new interchange;

$result=array('success'=>false,'id'=>0,'oid'=>'','brandname'=>'???');


if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['brand']) && isset($_GET['competitivepartnumber']))
{
 $partnumber=$_GET['partnumber'];
 $competitivepartnumber=$_GET['competitivepartnumber'];
 $brandAAIAID=$_GET['brand'];
 $userid=$_SESSION['userid'];

 if($pim->validPart($partnumber) && strlen($_GET['competitivepartnumber'])<=20 && strlen($_GET['brand'])==4)
 {
  if($id=$interchange->addInterchange($partnumber,$competitivepartnumber,$brandAAIAID,1,'EA','',''))
  {
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='competitor interchange ['.$brandAAIAID.':'.$competitivepartnumber.'] was added';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result['success']=true; $result['id']=$id; $result['oid']=$oid; $result['brandname']=$interchange->brandName($brandAAIAID);
 }
 echo json_encode($result);
}
?>
