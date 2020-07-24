<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
session_start();
$pim= new pim;
$qdb= new qdb;

if(isset($_SESSION['userid']) && isset($_GET['note']) && isset($_GET['qdbid']) && isset($_GET['qdbparms']))
{  
 $userid=$_SESSION['userid'];
 $qdbid=intval($_GET['qdbid']);
 $qdbparms=$_GET['qdbparms'];
 $result=true;
 
 $attributes=$pim->getAppAttributesByValue('note','note',$_GET['note']);
 
 foreach($attributes as $attribute)
 {
  if($pim->updateApplicationAttribute($attribute['id'], 'qdb', $qdbid , $qdbparms))
  {
   $applicationid=$attribute['applicationid'];
   $newoid=$pim->updateAppOID($applicationid);
   $pim->logAppEvent($applicationid, $userid, 'Note->Qdb conversion ['.$_GET['note'].']->['.$qdb->qualifierText($qdbid).']', $newoid);
  }
  else
  {
     $result=false;
  }
 }
 echo json_encode($result);
}
?>
