<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/configGetClass.php');

$starttime=time();

$pim=new pim();
$configGet = new configGet();
$logs=new logs();
$asset=new asset();


// delete (and document) orphan records in part_asset table (assets that were deleted and left behind a part connection)
$orphans=$asset->getOrphanPartAssetRecords();
foreach($orphans as $orphan)
{
 $asset->disconnectPartFromAsset($orphan['partnumber'], $orphan['id']);
 $newoid=$pim->updatePartOID($orphan['partnumber']);
 $pim->logPartEvent($orphan['partnumber'], 0, 'orphan asset connection to ['.$orphan['assetid'].'] was deleted by housekeeper', $newoid);
}
if(count($orphans))
{
 $logs->logSystemEvent('housekeeper', 0, 'Background houskeeper deleted '.count($orphans).' orphan part_asset records');    
}

// PIO re-calc for all active parts
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$updatedpartcount=0;
if($viogeography && $vioyearquarter)
{
 $activeparts=$pim->getParts('', 'contains', 'any', 'any', '2', 'any', 1000000); //array('partnumber'=>$row['partnumber'],'oid'=>$row['oid'],'parttypeid'=>$row['parttypeid'],'lifecyclestatus'=>$row['lifecyclestatus'],'partcategory'=>$row['partcategory'],'partcategoryname'=>$row['partcategoryname'],'replacedby'=>$row['replacedby'],'description'=>$row['description']);
 foreach($activeparts as $part)
 {
  $piorecords=$pim->getPartVIOrecords($part['partnumber'],$viogeography,$vioyearquarter);
  $needupdate=false; $foundrecord=false;
  foreach($piorecords as $piorecord)
  {
   if($piorecord['recordage']>1){$needupdate=true;}
   $foundrecord=true;
  }
  
  if($needupdate || !$foundrecord)
  {
   $viototal=$pim->partVIOexperian($part['partnumber'], $viogeography, $vioyearquarter);
   $updatedpartcount++;
  }
 }
}
else
{
 $logs->logSystemEvent('housekeeper', 0, 'Background housekeeper skipped part VIO updates because VIOdefaultGeography or VIOdefaultYearQuarter is not set in the config.'); 
}
$logs->logSystemEvent('housekeeper', 0, 'Background houskeeper updated VIO counts on '.$updatedpartcount. ' parts.'); 


// delete apps flagged as deleted (status = 1)
$appids=$pim->removeDeletedApps();
if(count($appids)>0)
{
 $logs->logSystemEvent('housekeeper', 0, 'Removed '.count($appids).' apps flagged for delete.');
}

$runtime=time()-$starttime;
if($runtime > 30)
{
 $logs->logSystemEvent('housekeeper', 0, 'Background houskeeper process ran for '.$runtime.' seconds');
}

?>