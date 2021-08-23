<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxConvertNoteToQdb.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
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
   $pim->logAppEvent($applicationid, $userid, 'Note>Qdb ['.$_GET['note'].']>['.$qdb->qualifierText($qdbid,explode('~', str_replace('|','',$qdbparms))).']', $newoid);
  }
  else
  {
     $result=false;
  }
 }
 echo json_encode($result);
}
?>
