<?php
/* stream a txt file of space-delimited list of assset filenames (ex: PRC914_1.jpg)
 * for use in compiling a zip script to build an asset bundle for a receiver profile.
 * If a filename contains a space, it will be wrapped in double-quotes.
 *
 * 
 * 
 */
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportAssetfilesListStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if(!isset($_SESSION['userid']))
{
 echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
 exit;
}

$assets = new asset;
$logs = new logs;

if(isset($_SESSION['userid']))
{
 $userid=$_SESSION['userid'];
}
else
{// no session exists - user not logged in
 $logs->logSystemEvent('accesscontrol',0, 'exportAssetfilesListStream.php - access denied to unauthenticated user from '.$_SERVER['REMOTE_ADDR']);
 exit; 
}

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$userid, 'exportAssetfilesListStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$filenamekeyedlist=array();
$filenamelist=array();

$profile=$pim->getReceiverprofileById(intval($_GET['receiverprofile']));

if($profile)
{
 $partcategories=$pim->getReceiverprofilePartcategories($profile['id']);
 $lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($profile['id']);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);
 foreach($partnumbers as $partnumber)
 {
  $digialassetconnections=$assets->getAssetsConnectedToPart($partnumber,true); // second parm cause non-public assets to be excluded from export
  if($digialassetconnections && count($digialassetconnections))
  {
   foreach($digialassetconnections as $digitalassetconnection)
   {
    $digitalassetrecords=$assets->getAssetRecordsByAssetid($digitalassetconnection['assetid']);
    foreach($digitalassetrecords as $digitalassetrecord)
    {
     if(!array_key_exists($digitalassetrecord['filename'], $filenamekeyedlist))
     {
      $filenamekeyedlist[$digitalassetrecord['filename']]='';
     }   
    }
   }
  }  
 }

 ksort($filenamekeyedlist );
 foreach($filenamekeyedlist as $filename=>$trash)
 {
  if(strstr($filename,' '))
  {// filename contains spaces - wrap it in doublequotes
   $filenamelist[]='"'.$filename.'"';      
  }
  else
  {// filename does not contain spaces
   $filenamelist[]=$filename;
  }
 }
 
 $filecontents= implode(' ', $filenamelist);

 $filename='assetfiles_'.date('Y-m-d').'.txt';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/octet-stream');
 header('Content-Length: ' . strlen($filecontents));
 header('Connection: close');    
 echo $filecontents;
}
?>
