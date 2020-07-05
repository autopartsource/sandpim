<?php
include_once('./class/pimClass.php');
session_start();
$pim= new pim;

$result=array('success'=>false,'id'=>0,'oid'=>'','descriptiontext'=>'???','descriptioncode'=>'???');

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['descriptiontext']) && isset($_GET['descriptioncode']) && isset($_GET['languagecode']))
{
 $partnumber=$_GET['partnumber'];
 $descriptiontext=$_GET['descriptiontext'];
 $descriptioncode=$_GET['descriptioncode'];
 $languagecode=$_GET['languagecode'];
 $userid=$_SESSION['userid'];

 if($pim->validPart($partnumber))
 {
  if($id=$pim->addPartDescription($partnumber,$descriptiontext,$descriptioncode,1,$languagecode))
  {
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='description ['.$descriptioncode.'('.$languagecode.'):'.$descriptiontext.'] was added';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result['success']=true; $result['id']=$id; $result['oid']=$oid; $result['descriptiontext']=$descriptiontext; $result['descriptioncode']=$descriptioncode;
 }
 echo json_encode($result);
}
?>
