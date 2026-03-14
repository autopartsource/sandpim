<?php
/*
 * intended to be executed from the command-line be a cron call ("php processReceiverAppStateCapture.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it. The job will be 
 * 
 * 
 * 
 */

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();
$jobs=$pim->getBackgroundjobs('ReceiverAppStateCapture','started');

if(count($jobs))
{
// include_once(__DIR__.'/class/vcdbClass.php');
// include_once(__DIR__.'/class/pcdbClass.php');
// include_once(__DIR__.'/class/qdbClass.php');
 include_once(__DIR__.'/class/logsClass.php');

// $vcdb=new vcdb();
// $pcdb=new pcdb();
// $qdb=new qdb();

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
 
 $capturemode=''; if(array_key_exists('CaptureMode', $parameters) && in_array($parameters['CaptureMode'],['REPLACE','MERGE'])){$capturemode=$parameters['CaptureMode'];}  
 
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $profilename=$profile['name'];

 $appstateswritten=0; $appstateschanged=0; $appstatesdeleted=0;
  
 // example of log: $pim->logBackgroundjobEvent($jobid, 'Partcategories in export:'.implode(',',$partcategories));
 
 $filename=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 } 

 $exportid=intval($parameters['exportid']);
 $exportheader=$pim->getExport($exportid);
 if(!$exportheader)
 {
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $logs->logSystemEvent('Export', 0, 'State capture of export ['.$exportid.'] for receiver ['.$receiverprofileid.'] failed for missing export table record. Jobid ['.$jobid.']');
  exit;
 }
 
 $exportdetailrecords=$pim->getExportDetail($exportid);
 if(count($exportdetailrecords)==0)
 {
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $logs->logSystemEvent('Export', 0, 'State capture of export ['.$exportid.'] for receiver ['.$receiverprofileid.'] failed for missing export_detail table records. Jobid ['.$jobid.']');
  exit;     
 }

  
 // mode selection
 switch ($capturemode)
 {
  // add or drop receiverstate records (keyed by appid)
  case 'MERGE':
      
   $localappidkeyedoids=[];
   foreach($exportdetailrecords as $exportdetailrecord)
   {
    if($exportdetailrecord['objecttype']=='APP')
    {
     $localappidkeyedoids[$exportdetailrecord['keynumeric']]=$exportdetailrecord['oid'];
    }
   }
   
   // drop apps from receiver's store because they are not present in the specific export cited
   $receiversappstates=$pim->getReceiverAppStates($receiverprofileid);
   $receiversappidkeyedoids=array();
   foreach($receiversappstates as $receiversappstate)
   {
    $receiversappid=$receiversappstate['applicationid'];
    $receiversoid=$receiversappstate['oid'];
    $localoid=$localappidkeyedoids[$receiversappid];
    $receiversappidkeyedoids[$receiversappid]=$receiversoid;    
    if(!array_key_exists($receiversappid, $localappidkeyedoids))
    { // this app id in the receiver's store does not exist locallly. Delete it
     $pim->deleteReceiverAppState($receiverprofileid, $receiversappid);
     $appstatesdeleted++;
    }
   }
   
   // proess needed adds/updates to the receiver's store
   foreach($localappidkeyedoids as $localappid=>$localoid)
   {
    if(array_key_exists($localappid, $receiversappidkeyedoids))
    { // update receiver's state rec to reflect this export was its source
     $pim->updateReceiverAppState($receiverprofileid, $receiversappid, $localoid, $exportid);
     $appstateschanged++;
    }
    else
    { // add new state record to receiver's store     
     $pim->writeReceiverAppState($receiverprofileid, $localappid, $localoid, $exportid);
     $appstateswritten++;
    }       
   }
   
   $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'Complete - '.$appstateswritten.' app states written, '.$appstatesdeleted.' deleted, '.$appstateschanged.' changed');
   $logs->logSystemEvent('Export', 0, 'Receiver app states ('.$appstateswritten.') captured from export ['.$exportid.'] to receiver profile ['.$receiverprofileid.']');   
   break;
  
  case 'REPLACE':
   // delete all existing app-state recs for the receiver         
   $pim->deleteReceiverAppState($receiverprofileid);
   // write exporet's app id's and oid's to receiver's state table
   foreach($exportdetailrecords as $exportdetailrecord)
   {
    if($exportdetailrecord['objecttype']=='APP')
    {
     $pim->writeReceiverAppState($receiverprofileid, $exportdetailrecord['keynumeric'], $exportdetailrecord['oid'], $exportid);
     $appstateswritten++;
    }
   }
   
   $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'Complete - '.$appstateswritten.' app states written');
   $logs->logSystemEvent('Export', 0, 'Receiver app states ('.$appstateswritten.') captured from export ['.$exportid.'] to receiver profile ['.$receiverprofileid.']');   
   break;
       
  default:
   $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
   $logs->logSystemEvent('Export', 0, 'State capture of export ['.$exportid.'] for receiver ['.$receiverprofileid.'] failed for unknown CaptureMode parm ['.$capturemode.']. Jobid ['.$jobid.']');
   break;
 }
 
}
else
{
 echo"no jobs pending\r\n";    
}
