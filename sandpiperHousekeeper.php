<?php
/*
 * To be run on a cron schedule (php CLI) every few minutes
 * problems found are added to the issue table (assuming they don't already exist there)
 * 
 * 
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/interchangeClass.php');
include_once(__DIR__.'/class/vcdbClass.php');
include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/padbClass.php');
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/sandpiperPrimaryClass.php');


$starttime=time();

$pim=new pim();
$asset=new asset();
$interchange=new interchange();
$pcdb=new pcdb();
$padb=new padb();
$vcdb=new vcdb();
$logs=new logs();
$sandpiperPrimary=new sandpiperPrimary();


// update slice hashes

$slices=$sandpiperPrimary->getAllSlices();
foreach ($slices as $slice)
{
 $hash=$sandpiperPrimary->updateSliceHash($slice['id']);   
 if($slice['slicehash']!=$hash)
 {
  $logs->logSystemEvent('sandpiper', 0, 'slice ['.$slice['description'].'] hash updated to ['.$hash.']');
 }
}


// update local pool with content dictated by plans
// ? what plans exist?
// go through all slices and add/drop grains based on the what's in the local pim content
// filegrains vs L2 grains? - I think the answer is to rename "filegrains" to "grains"







$runtime=time()-$starttime;
if($runtime > 10)
{
 $logs->logSystemEvent('auditor', 0, 'Background auditor process ran for '.$runtime.' seconds');
}

?>
