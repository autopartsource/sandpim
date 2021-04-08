<?php
include_once('./class/pimClass.php');
include_once('./class/assetClass.php');

$pim = new pim;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs = new logs;
 $userid=0;
 if(isset($_SESSION['userid'])){$userid=$_SESSION['userid'];}
 $logs->logSystemEvent('accesscontrol',$userid, 'exportAssetfilesListStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$assets = new asset;
$filecontents='';
$filenamekeyedlist=array();
$filenamelist=array();


$profile=$pim->getReceiverprofileById(intval($_GET['receiverprofile']));

if($profile)
{
 $partcategories=$pim->getReceiverprofilePartcategories($profile['id']);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories);
 foreach($partnumbers as $partnumber)
 {
  $digialassetconnections=$assets->getAssetsConnectedToPart($partnumber);
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
