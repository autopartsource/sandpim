<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxUpdateEXPIofPart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
$padb= new padb;
$pcdb=new pcdb;

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && isset($_GET['expi']) && isset($_GET['value']) && isset($_GET['languagecode']))
{
 $userid=$_SESSION['userid'];
 $partnumber=$_GET['partnumber'];
 $value=$_GET['value'];
 $languagecode=$_GET['languagecode'];
 $id='';
 $success=false;
 $part=$pim->getPart($partnumber);
 if($part)
 { // valid part
  $oid=$part['oid'];
  $EXPI=$_GET['expi'];
  
  if($pcdb->validEXPIcode($EXPI))
  { // this is a EXPI code
   $EXPIcodedescription=$pcdb->EXPIcodeDescription($EXPI);
   if(!$pim->partEXPIvalue($partnumber,$EXPI,$languagecode,false))
   {
    $eventtext='EXPI ['.$EXPI.'='.$value.'] was added for language code '.$languagecode;
    $id=$pim->writePartEXPI($partnumber, $EXPI, $value, $languagecode);
    $oid=$pim->updatePartOID($partnumber);
    $success=true;
   }
   else
   { // EXPI/language is already applied to part - update it
    $id=$pim->updatePartEXPI($partnumber, $EXPI, $languagecode, $value);
    $eventtext='EXPI ['.$EXPI.'] updated to: '.$value.' for language code '.$languagecode;
    $oid=$pim->updatePartOID($partnumber);
    $success=true;
   }
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);   
  }
  
  // touch any dependant parts (change their oids and write history records)  
  if($success)
  {
   $dependantparts=$pim->getPartnumbersByBasepart($partnumber);
   foreach($dependantparts as $dependantpart)
   {// each part that claims this one as a base
    $oid=$pim->updatePartOID($dependantpart);
    $pim->logPartEvent($dependantpart,$userid, $eventtext.' on basepart ['.$partnumber.']' ,$oid);  
   }
  }
  
  $result=array('success'=>$success, 'id'=>$id, 'EXPI'=>$EXPI, 'name'=>$EXPIcodedescription, 'value'=>$value, 'oid'=>$oid);
  echo json_encode($result);
 }
}
?>
