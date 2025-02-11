<?php
//include_once('./class/pimClass.php');
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening

$pim=new pim;
$jobs=$pim->getBackgroundjobs('ACESflatImport','started');

if(count($jobs))
{
 $file_name=$jobs[0]['inputfile'];
 $jobid=$jobs[0]['id'];
 
 $parametersstring=$jobs[0]['parameters'];
 $parameters=array();
 $chunks=explode(';',$parametersstring);
 foreach($chunks as $chunk)
 {
  $bits=explode(':',$chunk);
  if(count($bits)==2)
  {
   $parameters[$bits[0]]=$bits[1];
  }    
 }
 
 if(file_exists($file_name))
 {
  $partcategory=0; if(array_key_exists('partcategory', $parameters)){$partcategory=intval($parameters['partcategory']);}
  $input = file_get_contents($file_name);
  $pim->updateBackgroundjobRunning($jobid, date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'Starting with '.strlen($input).' bytes of flat input data. Category for new part creation is '.$partcategory);
  $app_count = $pim->createAppsFromText($input,$partcategory);          
  $pim->logBackgroundjobEvent($jobid, 'Done creating '.$app_count.' apps');
  $pim->updateBackgroundjobDone($jobid, 'complete', date('Y-m-d H:i:s'));
  unlink($file_name);
 }
}
?>
