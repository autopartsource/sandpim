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
include_once(__DIR__.'/class/vcdbClass.php');
include_once(__DIR__.'/class/pcdbClass.php');
include_once(__DIR__.'/class/logsClass.php');
include_once(__DIR__.'/class/sandpiperPrimaryClass.php');


$starttime=time();

$pim=new pim;
$pcdb=new pcdb();
$vcdb=new vcdb();
$logs=new logs();
$sandpiperPrimary=new sandpiperPrimary();

// part audits - grab random groups of parts
    // missing packages
    // missing (or zero) package elements
    // Pricing holes
    // Invalid PAdb attributes
    // missing assets

// --- get a random group of items to examine 
//$pim->recordIssue('SYSTEM/HEARTBEAT','test',1,'testtest','background auditor', '1234567890');

$partnumbergroupsize=100;
$partnumbers=$pim->getPartnumbersByRandom($partnumbergroupsize);

foreach($partnumbers as $partnumber)
{
    // Invalid part-type detection (according to the default PCdb)
    $part=$pim->getPart($partnumber);
    if($pcdb->parttypeName($part['parttypeid'])=='not found')
    {// part type id is not valid according to default PCdb
        $issuehash=md5('PART/REFERENCE/INVALID PARTTYPEID'.$partnumber.'0'.'invalid parttype id ('.$part['parttypeid'].')'.'background auditor');
        if(!$pim->getIssueByHash($issuehash))
        {// this issue is not already recorded 
            $pim->recordIssue('PART/REFERENCE/INVALID PARTTYPEID',$partnumber,0,'invalid parttype id ('.$part['parttypeid'].')','background auditor', $issuehash);
        }
    }
   
    // duplicate GTINs
    $partnumberswithsamegtin=$pim->getPartnumbersByGTIN($part['GTIN']);
    if(count($partnumberswithsamegtin)>1)
    {// multiple parts with this parts GTIN
        foreach($partnumberswithsamegtin as $otherpartnumber)
        {
           if($otherpartnumber != $partnumber)
           {
               $issuehash=md5('PART/GTIN/DUPLICATE'.$partnumber.'0'.'another part ('.$otherpartnumber.') shares the same GTIN'.'background auditor');
               if(!$pim->getIssueByHash($issuehash))
               {// this issue is not already recorded 
                   $pim->recordIssue('PART/GTIN/DUPLICATE',$partnumber,0,'another part ('.$otherpartnumber.') shares the same GTIN','background auditor', $issuehash);
               }
           }
       }
    }
    
    // Invalid check-digits on UPCs
    if(trim($part['GTIN'])!='')
    {
        $correctcheck=$pim->gtinCheckDigit($part['GTIN']);
        if($correctcheck!==false)
        {
            if(substr($part['GTIN'], -1) != $correctcheck)
            {
                $issuehash=md5('PART/GTIN/CHECKDIGIT'.$partnumber.'0'.'GTIN check digit is wrong ('.$part['GTIN'].'). It should be '.$correctcheck.'background auditor');
                if(!$pim->getIssueByHash($issuehash))
                {// this issue is not already recorded 
                    $pim->recordIssue('PART/GTIN/CHECKDIGIT',$partnumber,0,'GTIN check digit is wrong ('.$part['GTIN'].'). It should be '.$correctcheck,'background auditor', $issuehash);
                }
            }
        }
    }
    
    // lifecycle vs replacedby disagreements
    // if replacedby!='' then lifecycle status should be 7   
    if(trim($part['replacedby'])=='' && $part['lifecyclestatus']=='7')
    {
        $issuehash=md5('PART/LIFECYCLE/REPLACEDBY'.$partnumber.'0'.'Lifecycle status is Superseded, but replacedby is null'.'background auditor');
        if(!$pim->getIssueByHash($issuehash))
        {// this issue is not already recorded 
            $pim->recordIssue('PART/LIFECYCLE/REPLACEDBY',$partnumber,0,'Lifecycle status is Superseded, but replacedby is null','background auditor', $issuehash);
        }
    }
    if(trim($part['replacedby'])!='' && $part['lifecyclestatus']!='7')
    {
        $issuehash=md5('PART/LIFECYCLE/REPLACEDBY'.$partnumber.'0'.'Replacedby is populated, but lifecycle status is not 7'.'background auditor');
        if(!$pim->getIssueByHash($issuehash))
        {// this issue is not already recorded 
            $pim->recordIssue('PART/LIFECYCLE/REPLACEDBY',$partnumber,0,'Replacedby is populated, but lifecycle status is not 7','background auditor', $issuehash);
        }
    }

    if(trim($part['replacedby'])!='')
    {
        if(!$pim->getPart(trim($part['replacedby'])))
        {
            $issuehash=md5('PART/REPLACEDBY/INVALID'.$partnumber.'0'.'Replacedby is not a valid partnumber'.'background auditor');
            if(!$pim->getIssueByHash($issuehash))
            {// this issue is not already recorded 
                $pim->recordIssue('PART/REPLACEDBY/INVALID',$partnumber,0,'Replacedby is not a valid partnumber','background auditor', $issuehash);
            }
        }
    }
    
    
    
    
    
}



//---------------------------------------------------- 
// application audits
    // invalid parttype-position combinations
    // invalid VCdb references (basevehilce, etc)
    // 
   
$appids=$pim->getAppIDsByRandom(250);
foreach($appids as $appid)
{
    echo $appid."\n";
    $app=$pim->getApp($appid);
    
    // validate parttype/position combination
    
    if($app['parttypeid']!=0 && $app['positionid']!=0 && !$pcdb->validParttypePosition($app['parttypeid'], $app['positionid']))
    {
        $issuehash=md5('APP/REFERENCE/PARTTYPE-POSITION'.$appid.'PartType/Position ('.$app['parttypeid'].'/'.$app['positionid'].') combination is not valid'.'background auditor');
        if(!$pim->getIssueByHash($issuehash))
        {// this issue is not already recorded 
            $pim->recordIssue('APP/REFERENCE/PARTTYPE-POSITION','',$appid,'PartType/Position ('.$app['parttypeid'].'/'.$app['positionid'].') combination is not valid','background auditor', $issuehash);
        }
    }
        
    if($app['basevehicleid']==0)
    {// equipment style app
        
        
    }
    else
    {// basevid style app
        
        if(!$vcdb->getMMYforBasevehicleid($app['basevehicleid']))
        {
            $issuehash=md5('APP/REFERENCE/BASEVEHICLEID'.$appid.'BaseVehicleID ('.$app['basevehicleid'].') is not valid'.'background auditor');
            if(!$pim->getIssueByHash($issuehash))
            {// this issue is not already recorded 
                $pim->recordIssue('APP/REFERENCE/BASEVEHICLEID','',$appid,'BaseVehicleID ('.$app['basevehicleid'].') is not valid','background auditor', $issuehash);
            }
        }
    }
    
    // validate attributes (vcdb, qdb)
    
    
    
    
}








// asset audits
    // local asset files disagreeing with meta-data (filesize, hash, width/height)
    // URI files disagreeing with meta-data (filesize, hash, width/height)
    // orphaned (un-connected) assets
    
    




// sandpiper housekeeping


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


// update issue snoozes. look for status3 records with a snoozeduntil before now and set them back to status1
$pim->updateSnoozes();


$runtime=time()-$starttime;
if($runtime > 5)
{
 $logs->logSystemEvent('auditor', 0, 'Background auditor process ran for '.$runtime.' seconds');
}

?>
