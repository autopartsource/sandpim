<?php
/*
 * intended to be executed from the command-line with a cron entry 
 * on a cycle (mine is on a five minute cycle). It will query the db for the oldest job that 
 * is status "started" and execute it.
 * 
 * the crontab entry on my fedora31 box looks like this: */
//          */5 * * * 0,1,2,3,4,5,6 root /usr/bin/php /var/www/html/processFlatAppsExport.php &> /dev/null
 /* 
 * On my fedora 31 box, I had to apply a read/write SELinux policy to the 
 * directory where apache can write the exported files (/var/www/html/ACESexports
 * semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ACESexports(/.*)?"
 * restorecon -Rv /var/www/html/ACESexports/
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();
$jobs=$pim->getBackgroundjobs('ACESflatExport','started');


if(count($jobs))
{
 include_once(__DIR__.'/class/vcdbClass.php');
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/qdbClass.php');
 include_once(__DIR__.'/class/logsClass.php');

 $vcdb=new vcdb();
 $pcdb=new pcdb();
 $qdb=new qdb();

 $logs=new logs();

 $file_name=$jobs[0]['outputfile'];
 $jobid=$jobs[0]['id'];
 $pim->updateBackgroundjobRunning($jobid, date('Y-m-d H:i:s'));
 
 $parameters=array();
 $parameterbits=explode(';',$jobs[0]['parameters']);
 foreach($parameterbits as $parameterbit)
 {
  $temp=explode(':',$parameterbit); if(count($temp)==2){$parameters[$temp[0]]=$temp[1];}
 }

 
 $exportformat='default';
 if(array_key_exists('exportformat', $parameters)){$exportformat=$parameters['exportformat'];}
 $receiverprofileid=intval($parameters['receiverprofile']);
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
 $apps=$pim->getAppsByPartcategories($partcategories);

 $filename=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 } 
 
 
 $partnumberstemp=$pim->getPartnumbersByPartcategories($partcategories);
 
 $pim->logBackgroundjobEvent($jobid, 'export format: '.$exportformat);
 $pim->logBackgroundjobEvent($jobid, 'part categories in export: '.implode(',',$partcategories));
 $pim->logBackgroundjobEvent($jobid, 'part count in categories: '.count($partnumberstemp));
  
 
 $writeresult=false;
 
 if($fh=fopen($filename, 'a'))
 {     
  if($exportformat=='default')
  {
   foreach($apps as $app)
   {
    $vcdbattributesstring=''; $qdbattributesstring=''; $notesstring='';   
    foreach($app['attributes'] as $attribute)
    {
     switch ($attribute['type']) 
     {
      case 'vcdb': $vcdbattributesstring.=$attribute['name'].'|'.$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~'; break;
      case 'qdb': $qdbattributesstring.=$qdb->qualifierText($attribute['name'], explode('~', str_replace('|','',$attribute['value']))); break;
      case 'note':$notesstring.=$attribute['value'].'|'.$attribute['sequence'].'|'.$attribute['cosmetic'].'~'; break;
      default: break;
     } 
    }
    $record=$app['cosmetic']."\t".$app['basevehicleid']."\t".$app['partnumber']."\t".$app['parttypeid']."\t".$app['positionid']."\t".$app['quantityperapp']."\t".$app['partnumber']."\t".$vcdbattributesstring."\t".$qdbattributesstring."\t".$notesstring."\r\n";
    $writeresult=fwrite($fh, $record);  
   }
  }
     
  if($exportformat=='decoded')
  {
   $record='Make'."\t".'Model'."\t".'Year'."\t".'Partnumber'."\t".'Part-Type'."\t".'Position'."\t".'App-Quantity'."\t".'Fitment Qualifiers'."\r\n";
   $writeresult=fwrite($fh, $record);  
   foreach($apps as $app)    
   {
    $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
    $niceattributes = array();
    foreach ($app['attributes'] as $appattribute) 
    {
     if ($appattribute['type'] == 'vcdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']);}
     if ($appattribute['type'] == 'qdb') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);}
     if ($appattribute['type'] == 'note') {$niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']);}
    }

    $nicefitmentarray = array();    
    foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
    $record=$mmy['makename']."\t".$mmy['modelname']."\t".$mmy['year']."\t".$app['partnumber']."\t".$pcdb->parttypeName($app['parttypeid'])."\t".$pcdb->positionName($app['positionid'])."\t".$app['quantityperapp']."\t".implode('; ', $nicefitmentarray)."\r\n";
    $writeresult=fwrite($fh, $record);  
   }
  }
  
  fclose($fh);
  $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'Flat applications file ['.$filename.'] created containing '.count($apps).' apps for profile id '.$receiverprofileid);
  $logs->logSystemEvent('Export', 0, 'Flat applications file ['.$filename.'] (jobid:'.$jobid.') exported by houskeeper; apps: '.count($apps));
 }
 else
 { // open failed
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
  $logs->logSystemEvent('Export', 0, 'Flat applications file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied?) during houskeeper processing; apps: '.count($apps));     
 }
 
}
else
{
 echo"no jobs pending\r\n";    
}
?>
