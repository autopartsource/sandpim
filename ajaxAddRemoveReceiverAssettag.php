<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveReceiverAssettag.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$logs= new logs;
$pcdb = new pcdb;
$asset = new asset;
$result=array('success'=>false, 'id'=>'');

if(isset($_SESSION['userid']) && isset($_GET['receiverprofileid']) && isset($_GET['action']))
{
 $userid=intval($_SESSION['userid']);
 $receiverprofileid=intval($_GET['receiverprofileid']);

 $receiverprofile=$pim->getReceiverprofileById($receiverprofileid);
         
 if($receiverprofile)
 {
  switch($_GET['action'])
  {
   case 'add':
    $assettagid=intval($_GET['assettagid']);
    $result['id']=$pim->addAssettagToReceiverProfile($receiverprofileid, $assettagid);
    $result['tagtext']=$asset->assettagText($assettagid);
    $result['success']=true;
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Asset tag ['.$result['tagtext'].'] added to receiver profile: '.$receiverprofile['name']);
   break;

   case 'remove':
    $recordid=intval($_GET['recordid']);
    
    // figure out what the tag is before revoming it by record id
    $tagtext='';//$asset->assettagText($assettagid);    
    $connectedassettags=$pim->getAssettagsForReceiverprofile($receiverprofileid);
    foreach($connectedassettags as $assettag){if($assettag['id']==$recordid){$tagtext=$assettag['tagtext'];}}
    
    $result['success']=$pim->removeAssettagFromReceiverProfile($recordid, $receiverprofileid);
    $logs->logSystemEvent('receiverprofile',$_SESSION['userid'],'Asset tag ['.$tagtext.'] removed from receiver: '.$receiverprofile['name']);
   break;

   default:
   break;
  }
 }
 echo json_encode($result);
}?>
