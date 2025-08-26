<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * housekeeping fixes/changes things - vs auditor finds and reports problems 
 * 
 * Test change
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/configGetClass.php');
include_once(__DIR__.'/class/vcdbClass.php');


$starttime=time();

$vioyearquarterref='2023Q4';



$pim=new pim();
$configGet = new configGet();
$logs=new logs();
$asset=new asset();
$vcdb=new vcdb();

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
 $logs->logSystemEvent('housekeeper', 0, 'Housekeeper deleted '.count($orphans).' orphan part_asset records');    
}

// PIO re-calc for all active parts
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$updatedpartcount=0;
if($viogeography && $vioyearquarter)
{
 $basevehicles=$vcdb->getAllBaseVehicles(); //     $basevehicles[$row['BaseVehicleID']] = array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'vehicletypeid'=>$row['VehicleTypeID'])
 $partsavail=$pim->getParts('', 'contains', 'any', 'any', '2', 'any', 100000); //array('partnumber'=>$row['partnumber'],'oid'=>$row['oid'],'parttypeid'=>$row['parttypeid'],'lifecyclestatus'=>$row['lifecyclestatus'],'partcategory'=>$row['partcategory'],'partcategoryname'=>$row['partcategoryname'],'replacedby'=>$row['replacedby'],'description'=>$row['description']);
 $partsannounced=$pim->getParts('', 'contains', 'any', 'any', '3', 'any', 100000);
 $partsdisconued=$pim->getParts('', 'contains', 'any', 'any', '8', 'any', 100000);
 
 $activeparts= array_merge($partsavail,$partsannounced,$partsdisconued);
         
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
   $pim->computePartVIO($part['partnumber'], $viogeography, $vioyearquarter, $basevehicles);
   $updatedpartcount++;
  }
  
  $piorecordscurrent=$pim->getPartVIOrecords($part['partnumber'],$viogeography,$vioyearquarter); // 'id'=>$row['id'], 'partnumber'=>$row['partnumber'], 'yearquarter'=>$row['yearQuarter'],'geography'=>$row['geography'], 'capturedate'=>$row['capturedate'],'vehiclecount'=>$row['vehicleCount'],'recordage'=>$row['age'],'startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'meanyear'=>$row['meanyear'],'growthtrend'=>$row['growthtrend']
  $piorecordsref=$pim->getPartVIOrecords($part['partnumber'],$viogeography,$vioyearquarterref); // 'id'=>$row['id'], 'partnumber'=>$row['partnumber'], 'yearquarter'=>$row['yearQuarter'],'geography'=>$row['geography'], 'capturedate'=>$row['capturedate'],'vehiclecount'=>$row['vehicleCount'],'recordage'=>$row['age'],'startyear'=>$row['startyear'],'endyear'=>$row['endyear'],'meanyear'=>$row['meanyear'],'growthtrend'=>$row['growthtrend']
  if(count($piorecordscurrent) && count($piorecordsref))
  {
   $vionow=floatval($piorecordscurrent[0]['vehiclecount']);
   $viothen=floatval($piorecordsref[0]['vehiclecount'])+1;
   $viotrend=round((($vionow-$viothen)/$viothen)*100,2);
   $pim->updatePartVIOgrowthtrend($part['partnumber'],$viogeography,$vioyearquarter,$viotrend);
  }  
 } 
}
else
{
 $logs->logSystemEvent('housekeeper', 0, 'Housekeeper skipped part VIO updates because VIOdefaultGeography or VIOdefaultYearQuarter is not set in the config.'); 
}
$logs->logSystemEvent('housekeeper', 0, 'Housekeeper updated VIO stats on '.$updatedpartcount. ' parts.'); 

// delete apps flagged as deleted (status = 1)
$appids=$pim->removeDeletedApps();
if(count($appids)>0)
{
 $logs->logSystemEvent('housekeeper', 0, 'Removed '.count($appids).' apps flagged for delete.');
}

// find and fix free-from fitment notes that contain ";"
// example (contains multiple semicolons)
//+---------+---------------+------+----------------------------------+------+----------+----------+
//| id      | applicationid | name | value                            | type | sequence | cosmetic |
//+---------+---------------+------+----------------------------------+------+----------+----------+
//| 4453807 |       3727376 | note | 10.25" Rotor; 4 Lugs; 8.66" Drum | note |        1 |        0 |
//+---------+---------------+------+----------------------------------+------+----------+----------+

$fixedattributecount=0;
$badattributes=$pim->getAppAttributesByValue('note', 'note', '%;%'); // get offending notes that need splitting    //$attributes[]=array('id'=>$row['id'],'applicationid'=>$row['applicationid'],'name'=>$row['name'],'value'=>$row['value'],'type'=>$row['type'],'sequence'=>$row['sequence'],'cosmetic'=>$row['cosmetic']);
$logs->logSystemEvent('housekeeper', 0, 'Housekeeper found '.count($badattributes).' fitment notes to be split');

foreach($badattributes as $badattribute)
{
 $noteparts=explode(';',$badattribute['value']);
 foreach($noteparts as $iteration=>$notepart)
 {
  if($iteration==0)
  {// this is first chunck - it will retain it's original record instance and be updated in-place
   $pim->updateApplicationAttribute($badattribute['id'], 'note', 'note', trim($notepart));      
  } 
  else
  {// this is a subsequent chunk of the note - it will be inserted as a new attribute record with same sequence as original
   $pim->addNoteAttributeToApp($badattribute['applicationid'], trim($notepart), $badattribute['sequence'], $badattribute['cosmetic'], false);
  }
 }
 
 $newoid=$pim->updateAppOID($badattribute['applicationid']);
 $pim->logAppEvent($badattribute['applicationid'], 0, 'note ['.$badattribute['value'].'] was split into '.count($noteparts).' notes by the housekeeper', $newoid);
 
 $fixedattributecount++;
 if($fixedattributecount>=1000){break;}
}

if($fixedattributecount>0){$logs->logSystemEvent('housekeeper', 0, 'Housekeeper split '.$fixedattributecount.' app notes');}

// VCdb integrity check
$integrityissues=$vcdb->integrityCheck();
if(count($integrityissues)==0)
{
 $logs->logSystemEvent('housekeeper', 0, 'VCdb integrity check clean');
}
else
{
 $logs->logSystemEvent('housekeeper', 0, 'VCdb integrity check failed: '.implode(',',$integrityissues));
}






$runtime=time()-$starttime;
if($runtime > 30)
{
 $logs->logSystemEvent('housekeeper', 0, 'Housekeeper process ran for '.$runtime.' seconds');
}

// clear clipboard content older than 2 days for all users
$pim->deleteOldClipboardObjects(2);

?>