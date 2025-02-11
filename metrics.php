<?php
/*
 * To be run on a cron schedule (php CLI) every day
 * problems found are added to the issue table (assuming they don't already exist there)
 * 
 * 
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
include_once(__DIR__.'/class/kpiClass.php');
include_once(__DIR__.'/class/assetClass.php');
include_once(__DIR__.'/class/interchangeClass.php');


$starttime=time();

$pim=new pim();
$kpi=new kpi();
$asset=new asset();
$interchange=new interchange();


$allparts=$pim->getParts('', 'contains', 'any', 'any', '2', 'any', 1000000);

$missingscounts=0;
foreach($allparts as $part)
{
    $assetrecords=$asset->getAssetsConnectedToPart($part['partnumber']);
    if(count($assetrecords)==0)
    {
        $missingscounts++;
    }
}
$kpi->recordMetric('ACTIVE PARTS MISSING ASSETS', $missingscounts);
$kpi->recordMetric('ACTIVE PART COUNT', count($allparts));


$allassets=$asset->getAssets('', 'startswith', 'any', 'any', '', 'any', 'public', '', '', 'startswith', '', 'startswith', 1000000);
$kpi->recordMetric('PUBLIC ASSET COUNT', count($allassets));

$kpi->recordMetric('ACTIVE APPLICATION COUNT', $pim->countAppsByPartcategories([]));



?>
