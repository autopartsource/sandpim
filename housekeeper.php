<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/configGetClass.php');

$starttime=time();

$pim=new pim();
$configGet = new configGet();
$logs=new logs();


// PIO re-calc for all active parts
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$updatedpartcount=0;
if($viogeography && $vioyearquarter)
{
 $activeparts=$pim->getParts('', 'contains', 'any', 'any', '2', 1000000); //array('partnumber'=>$row['partnumber'],'oid'=>$row['oid'],'parttypeid'=>$row['parttypeid'],'lifecyclestatus'=>$row['lifecyclestatus'],'partcategory'=>$row['partcategory'],'partcategoryname'=>$row['partcategoryname'],'replacedby'=>$row['replacedby'],'description'=>$row['description']);
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





$runtime=time()-$starttime;
if($runtime > 30)
{
 $logs->logSystemEvent('housekeeper', 0, 'Background houskeeper process ran for '.$runtime.' seconds');
}

?>