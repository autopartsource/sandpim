<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/assetClass.php');
include_once('./class/configGetClass.php');

// to automate the interaction with external systems looking to answer the question: "what are the assets for this part"
//

$pim= new pim;
$logs = new logs;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs->logSystemEvent('accesscontrol',0, 'partAssetAPI.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$configGet = new configGet();

$enabled=$configGet->getConfigValue('partAssetAPIenabled','no');
$assets_linked_to_item=array();

if($enabled=='yes')
{
 $asset=new asset();
 $part=$pim->getPart($_GET['partnumber']);
 if($part)
 {
  $assets_linked_to_item= $asset->getAssetsConnectedToPart($part['partnumber']);
  $logs->logSystemEvent('INFO', 0, 'partAssetAPI was queried with for partnumber ['.$_GET['partnumber'].'] by '.$_SERVER['REMOTE_ADDR']);  
 }
 else
 {// part number given is not valid
  $logs->logSystemEvent('SECURITY', 0, 'partAssetAPI was queried with an invalid partnumber ['.$_GET['partnumber'].'] by '.$_SERVER['REMOTE_ADDR']);
 }
 echo json_encode($assets_linked_to_item);
}
else
{
 $logs->logSystemEvent('SECURITY', 0, 'partAssetAPI.php was queried by '.$_SERVER['REMOTE_ADDR'].', but is not enabled. Set config value partAssetAPIenabled to yes to enable.');    
}