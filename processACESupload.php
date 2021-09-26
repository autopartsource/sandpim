<?php
//include_once('./class/pimClass.php');
include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening

$pim=new pim;
$jobs=$pim->getBackgroundjobs('ACESxmlImport','started');

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
     
 
 

 //load XML file
 if(file_exists($file_name))
 {
  $xml = simplexml_load_file($file_name);
  $app_count=count($xml->App);

  //$pim->updateBackgroundjob($jobid,'processing','loaded xml - containing '.$app_count.' apps',1,'0000-00-00 00:00:00');
  $pim->updateBackgroundjobStatus($jobid, 'processing', 0);
  if(array_key_exists('partcategory', $parameters))
  {
   $imported_app_count=$pim->createAppFromACESsnippet($xml,$parameters['partcategory']);
  }
  else
  {
   $imported_app_count=$pim->createAppFromACESsnippet($xml);
  }

  if(unlink($file_name))
  { // successful delete of ACES xml file
   //$pim->updateBackgroundjob($jobid,'complete','imported '.$imported_app_count.' apps',100,date('Y-m-d H:i:s'));
   $pim->updateBackgroundjobStatus($jobid, 'complete', 100);
  }
 }
}
?>
