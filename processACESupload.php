<?php
include_once('/var/www/html/class/pimClass.php');

$pim=new pim;
$jobs=$pim->getBackgroundjobs('ACESxmlImport','started');

if($jobs)
{
 $file_name=$jobs[0]['inputfile'];
 $jobid=$jobs[0]['id'];
 $parameters=array('appcategory'=>1);

 $parameterbits=explode(';',$jobs[0]['parameters']);
 foreach($parameterbits as $parameterbit)
 {
  $temp=explode(':',$parameterbit); if(count($temp)==2){$parameters[$temp[0]]=$temp[1];}
 }


 //load XML file
 if(file_exists($file_name))
 {
  $xml = simplexml_load_file($file_name);
  $app_count=count($xml->App);

  $pim->updateBackgroundjob($jobid,'processing','loaded xml - containing '.$app_count.' apps',1,'0000-00-00 00:00:00');
  $imported_app_count=$pim->createAppFromACESsnippet($xml,$parameters['appcategory']);

  if(unlink($file_name))
  { // successful delete of ACES xml file
   $pim->updateBackgroundjob($jobid,'complete','imported '.$imported_app_count.' apps',100,date('Y-m-d H:i:s'));
  }
  else
  { // delete of file failed
   $pim->updateBackgroundjob($jobid,'complete','imported '.$imported_app_count.' apps - but failed to delete ACES file',100,date('Y-m-d H:i:s'));
  }
 }
 else
 { // file does not exist
  $pim->updateBackgroundjob($jobid,'canceled','file ['.$file_name.'] does not exist',0,'0000-00-00 00:00:00');
 }
}
?>
