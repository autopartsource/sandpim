<?php
/*
 * intended to be executed from the command-line be a cron call ("php processACESexport.php")
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


if(count($jobs))
{
 include_once(__DIR__.'/class/vcdbClass.php');
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/qdbClass.php');
 include_once(__DIR__.'/class/logsClass.php');
 include_once(__DIR__.'/class/ACES4_1GeneratorClass.php');

 $vcdb=new vcdb();
 $pcdb=new pcdb();
 $qdb=new qdb();

 $logs=new logs();
 $generator=new ACESgenerator();

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
 $updatereceiverappstates=false; if(array_key_exists('UpdateReceiverAppStates', $parameters) && $parameters['UpdateReceiverAppStates']=='yes'){$updatereceiverappstates=true;}
 $exporttype='FULL'; if(array_key_exists('ExportType', $parameters) && $parameters['ExportType']=='UPDATE'){$exporttype='UPDATE';}
 $exportpurpose=''; if(array_key_exists('ExportPurpose', $parameters) && trim($parameters['ExportPurpose'])!=''){$exportpurpose='Exported for '.$parameters['ExportPurpose'];}
  
 
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $profilename=$profile['name'];

 $appstateswritten=0;
 $appstateschanged=0;
 $appstatesdeleted=0;
 
 $lifecyclestatuslist=array();
 $lifecyclestatusestemp=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
 foreach ($lifecyclestatusestemp as $status){$lifecyclestatuslist[]=$status['lifecyclestatus'];}

 
 $partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
 $apps=$pim->getAppsByPartcategories($partcategories,$lifecyclestatuslist);
 $parttranslations=$pim->getReceiverprofileParttranslations($receiverprofileid);
 
 $pim->logBackgroundjobEvent($jobid, 'Partcategories in export:'.implode(',',$partcategories));
 
 $filename=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 } 

 $header=array('Company'=>'not set in profile ['.$profile['name'].']','SenderName'=>'not set in profile ['.$profile['name'].']', 'SenderPhone'=>'not set in profile ['.$profile['name'].']','BrandAAIAID'=>'XXXX','DocumentTitle'=>'not set in profile ['.$profile['name'].']','TransferDate'=>date('Y-m-d'),'EffectiveDate'=>date('Y-m-d'),'SubmissionType'=>$exporttype,'VcdbVersionDate'=>$vcdb->version(),'QdbVersionDate'=>$qdb->version(),'PcdbVersionDate'=>$pcdb->version());
 if(array_key_exists('Company', $keyedprofile)){$header['Company']=$keyedprofile['Company'];}
 if(array_key_exists('SenderName', $keyedprofile)){$header['SenderName']=$keyedprofile['SenderName'];}
 if(array_key_exists('SenderPhone', $keyedprofile)){$header['SenderPhone']=$keyedprofile['SenderPhone'];}
 if(array_key_exists('BrandAAIAID', $keyedprofile)){$header['BrandAAIAID']=$keyedprofile['BrandAAIAID'];}
 if(array_key_exists('DocumentTitle', $keyedprofile)){$header['DocumentTitle']=$keyedprofile['DocumentTitle'];}
 if(array_key_exists('ApprovedFor', $keyedprofile)){$header['ApprovedFor']=$keyedprofile['ApprovedFor'];}
 if(array_key_exists('MapperCompany', $keyedprofile)){$header['MapperCompany']=$keyedprofile['MapperCompany'];}
 if(array_key_exists('MapperContact', $keyedprofile)){$header['MapperContact']=$keyedprofile['MapperContact'];}
 
 $includecosmeticapps=false; if(array_key_exists('IncludeCosmeticApps', $keyedprofile) && $keyedprofile['IncludeCosmeticApps']=='yes'){$includecosmeticapps=true;}
 $includecosmeticattributes=false; if(array_key_exists('IncludeCosmeticAttributes', $keyedprofile) && $keyedprofile['IncludeCosmeticAttributes']=='yes'){$includecosmeticattributes=true;} 
 $suppressduplicateapps=false; if(array_key_exists('SuppressDuplicateApps', $keyedprofile) && $keyedprofile['SuppressDuplicateApps']=='yes'){$suppressduplicateapps=true;} 
 $descriptiontonmfrlabel=''; if(array_key_exists('DescriptionToMfrlabel', $keyedprofile) && $keyedprofile['DescriptionToMfrlabel']!=''){$descriptiontonmfrlabel=$keyedprofile['DescriptionToMfrlabel'];}
 $suppressrefs=false; if(array_key_exists('SuppressRefs', $keyedprofile) && $keyedprofile['SuppressRefs']=='yes'){$suppressrefs=true;}
 $generatoroptions=array('IncludeCosmeticApps'=>$includecosmeticapps,'IncludeCosmeticAttributes'=>$includecosmeticattributes,'SuppressDuplicateApps'=>$suppressduplicateapps,'DescriptionToMfrlabel'=>$descriptiontonmfrlabel,'ProfileName'=>$profilename,'SuppressRefs'=>$suppressrefs); 
 
 $assetapps=array();
 $netapps=array();
 
 // roll thgough apps to extract a list of parts for the purpose of getting descriptions to jam into mfr label (if that option was selected) 
 $partdescriptions=array();
 if($descriptiontonmfrlabel!='')
 {
  foreach($apps as $app)
  {
   if(!array_key_exists($app['partnumber'],$partdescriptions))
   {
    $descriptionstemp=$pim->getPartDescriptions($app['partnumber']);
    foreach($descriptionstemp as $descriptiontemp)
    {
     if($descriptiontemp['descriptioncode']==$descriptiontonmfrlabel)
     {
      $partdescriptions[$app['partnumber']]=$descriptiontemp['description'];
      break; // use first instance found
     }
    }
   }
  }
 }

 
 // record the export for future nets a posterity
 $appidkeyedoids=array();
 $exportid=$pim->logExport($receiverprofileid, 'ACES-xml', $filename, count($apps).' apps '.$exportpurpose);
 $pim->logBackgroundjobEvent($jobid, 'Export '.$exportid.' created for this job');

 foreach($apps as $app)
 {
  $appidkeyedoids[$app['id']]=$app['oid'];
  $pim->logExportDetail($exportid, 'APP', serialize($app), $app['id'], '', $app['oid']);
  
  $receiversoid=$pim->getReceiverAppState($receiverprofileid, $app['id']);
  if($receiversoid!==false)
  { // appid exists in local cache representing the receiver's dataset
      
   if($receiversoid!=$app['oid'])
   { // current app state does not match receiver's - need to export it for them and update local model

    // the "D" app must be pulled from history
    $historicalapp=$pim->getAppFromHistoricalExport($app['id'], $receiversoid);
    if($historicalapp)
    {
     $historicalapp['action']='D';
     $netapps[]=$historicalapp;
    }
    else
    { // no bueno - we dont have a record of this app to put in as a "D" not much we can do but log it
     $pim->logBackgroundjobEvent($jobid, 'app '.$app['id'].' with state: '.$receiversoid.' was needed for constructing a D app, but not found in export_detail' );
    }

    $netapps[]=$app;
     
    if($updatereceiverappstates)
    {
     $pim->deleteReceiverAppState($receiverprofileid, $app['id']);
     $pim->writeReceiverAppState($receiverprofileid, $app['id'], $app['oid'],$exportid);
     $appstateschanged++;
    }
   }
  }
  else
  {// appid is not on-record for this reciever - add it and include in export
   $netapps[]=$app;
   if($updatereceiverappstates)
   {
    $pim->writeReceiverAppState($receiverprofileid, $app['id'], $app['oid'],$exportid);
    $appstateswritten++;
   }
  }
 }


 // roll through local store of receiver's app states to determine if anything needs to be dropped 
 $receiversappstates=$pim->getReceiverAppStates($receiverprofileid);
 foreach($receiversappstates as $receiversappstate)
 {
  if(!array_key_exists($receiversappstate['applicationid'], $appidkeyedoids))
  { // this app in the receiver's store does not exist in the current apps. Pull it from history for including as a D app      
   $historicalapp=$pim->getAppFromHistoricalExport($receiversappstate['applicationid'], $receiversappstate['oid']);
   if($historicalapp)
   {
    $historicalapp['action']='D';
    $netapps[]=$historicalapp;
    if($updatereceiverappstates)
    {
     $pim->deleteReceiverAppState($receiverprofileid, $receiversappstate['applicationid']);
     $appstatesdeleted++;
    }    
   }
   else
   { // no bueno - we dont have a record of this app to put in as a "D" not much we can do but log it
    $pim->logBackgroundjobEvent($jobid, 'app '.$receiversappstate['applicationid'].' with state: '.$receiversappstate['oid'].' was needed for constructing a D app, but not found in export_detail' );
   }
  }
 }

 
 
 if($exporttype=='UPDATE')
 {
  $doc=$generator->createACESdoc($header,$netapps,$assetapps, $parttranslations, $partdescriptions, $generatoroptions);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);
 }
 else
 { // FULL type
  $doc=$generator->createACESdoc($header,$apps,$assetapps, $parttranslations, $partdescriptions, $generatoroptions);//,$descriptions,$prices,$expi,$attributes,$packages,$kits,$interchanges,$assets);     
 }

 $pim->logBackgroundjobEvent($jobid, 'Receiver app states - written:'.$appstateswritten.'; changed (added then deleted):'.$appstateschanged.'; deleted:'.$appstatesdeleted);
 
 $schemaresults=array();
 libxml_use_internal_errors(true);
 if(!$doc->schemaValidate(__DIR__.'/ACES_4_1_XSDSchema_Rev1.xsd'))
 {
  $schemaerrors = libxml_get_errors();
  foreach ($schemaerrors as $schemaerror)
  {
   switch ($schemaerror->level) 
   {
    case LIBXML_ERR_WARNING:
 //    $schemaresults[]='Warning code '. $schemaerror->code;
     break;
    case LIBXML_ERR_ERROR:
  //   $schemaresults[]='Error code '.$schemaerror->code;
     break;
    case LIBXML_ERR_FATAL:
     $schemaresults[]='Fatal Error code '.$schemaerror->code;
     break;
   }
   $schemaresults[]=trim($schemaerror->message);   
  }
  libxml_clear_errors();

  //echo 'schema validations failed';
  foreach($schemaresults as $schemaresult)
  {
      $pim->logBackgroundjobEvent($jobid, $schemaresult);
  }   
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $logs->logSystemEvent('Export', 0, 'ACES file ['.$filename.'] (jobid:'.$jobid.') export failed (schema violation) during background processing; apps:'.count($apps));
 }
 else
 {
  //echo 'schema validations success';
  $doc->formatOutput=true;
  $writeresult=$doc->save($filename);
  if($writeresult)
  {
   $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'ACES file ['.$filename.'] created containing '.count($apps).' apps');
   $logs->logSystemEvent('Export', 0, 'ACES file ['.$filename.'] (jobid:'.$jobid.') exported by background processor; apps:'.count($apps));   
  }
  else
  {  // writing the output xml file failed
   //echo 'output file write failed';
   $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
   $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
   $logs->logSystemEvent('Export', 0, 'ACES file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied) during background processing; apps:'.count($apps));
  }
 }
}
else
{
 echo"no jobs pending\r\n";    
}
?>
