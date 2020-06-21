<?php
/*
 * intended to be executed from the command-line be a cron call ("php processFlatAppsExport.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it. The job will be 
 * 
 * On my fedora 31 box, I had to apply a read/write SELinux policy to the 
 * directory where apache can write the exported files (/var/www/html/ACESexports
 * semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/ACESexports(/.*)?"
 * restorecon -Rv /var/www/html/ACESexports/
 * 
 * 
 */


include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();
$jobs=$pim->getBackgroundjobs('ACESxmlExport','started');


if($jobs)
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
 
 $writeresult=false;
 
 if($fh=fopen($filename, 'a'))
 {
  foreach($apps as $app)
  {
   $record=$app['cosmetic']."\t".$app['basevehicleid']."\t".$app['partnumber']."\t".$app['parttypeid']."\t".$app['positionid']."\t".$app['positionid']."\t".$app['quantityperapp']."\t".$app['partnumber']."\t".$vcdbattributesstring."\t".          $qdbattributesstring."\t".$notesstring."\r\n";
   $writeresult=fwrite($fh, $record);
  }
  fclose($filename);
 }
 
 
 if($writeresult)
 {
  $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'Flat applications file ['.$filename.'] created containing '.count($apps).' apps');
  $logs->logSystemEvent('Export', 0, 'Flat applications file ['.$filename.'] (jobid:'.$jobid.') exported by houskeeper; apps:'.count($apps));
 }
 else
 {  // writing the output xml file failed
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
  $logs->logSystemEvent('Export', 0, 'Flat applications file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied) during houskeeper processing; apps:'.count($apps));
 }
}
else
{
 echo"no jobs pending\r\n";    
}
?>
