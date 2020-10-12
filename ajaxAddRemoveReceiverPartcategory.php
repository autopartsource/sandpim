<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
session_start();
$pim= new pim;
$logs= new logs;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['partcategoryid']) && isset($_GET['receiverprofileid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $partcategoryid=intval($_GET['partcategoryid']);
 $receiverprofileid = intval($_GET['receiverprofileid']);

 $partcategory=$pim->getPartCategory($partcategoryid);
 $receiverprofile=$pim->getReceiverprofileById($receiverprofileid);
         
 if($receiverprofile && $partcategory)
 {
  switch($_GET['action'])
  {
   case 'add':
    $result['id']=$pim->addPartcategoryToReceiverProfile($receiverprofileid, $partcategoryid);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofilechange',$_SESSION['userid'],'Partcategory: '.$partcategory['name'].' added to receiverprofile: '.$receiverprofile['name']);
   break;

   case 'remove':
    $pim->removePartcategoryFromReceiverProfile($receiverprofileid, $partcategoryid);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofilechange',$_SESSION['userid'],'Partcategory: '.$partcategory['name'].' removed from receiverprofile: '.$receiverprofile['name']);
   break;

   default:
   break;
  }
 }
 echo json_encode($result);
}?>
