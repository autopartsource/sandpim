<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddClipboardAppsToPart.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

$vcdb = new vcdb;

session_start();
$newapps=array();

if(isset($_SESSION['userid']) && isset($_GET['partnumber']))
{
 $logs= new logs;
 $userid=intval($_SESSION['userid']);
 $part=$pim->getPart($_GET['partnumber']);

 if($part)
 {
  $clipboardapps=$pim->getClipboard($userid, 'app');
  $existingappids=array();
  foreach($clipboardapps as $clipboardapp)
  {
   $app=$pim->getApp(intval($clipboardapp['objectkey']));
   if($app){$existingappids[]=$app['id'];}      
  }
  
  $newappids=$pim->cloneAppsToPart($part['partnumber'], $existingappids);
  foreach($newappids as $newappid)
  {
   $pim->logAppEvent($newappid, $userid, 'app created from clipboard', '');
  }
  $pim->logPartEvent($part['partnumber'], $userid, count($newappids).' apps pasted from clipboard', '');
  $rawnewapps=$pim->getAppsByPartnumber($part['partnumber']);
  
  //niceify the apps
  foreach($rawnewapps as $newapp)
  {
   $niceappdescription=$vcdb->niceMMYofBasevid($newapp['basevehicleid']);  
   $newapps[]=array('id'=>$newapp['id'],'niceappdescription'=>$niceappdescription);
  }
 }
}

echo json_encode($newapps);
?>
