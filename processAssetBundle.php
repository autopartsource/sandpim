<?php
/*
 * intended to be executed from the command-line be a cron call ("php processAssetBundle.php")
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

$starttime=time();

include_once(__DIR__.'/class/pimClass.php');  // the __DIR__ will provide the full path for when command-line (cronjob) execution is happening
$pim = new pim();
$jobs=$pim->getBackgroundjobs('AssetBundle','started');

if(count($jobs))
{
 include_once(__DIR__.'/class/assetClass.php');
 include_once(__DIR__.'/class/logsClass.php');
 
 $assets = new asset();
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
 $verbosity=0; if(array_key_exists('verbosity', $parameters)){intval($parameters['verbosity']);}
 $verifyhashes=false;if(array_key_exists('verifyhashes', $parameters)){$verifyhashes=true;}
  
 $profile=$pim->getReceiverprofileById($receiverprofileid);
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';
 $profilename=$profile['name'];
 
 $lifecyclestatuslist=array();
 $lifecyclestatusestemp=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
 foreach ($lifecyclestatusestemp as $status){$lifecyclestatuslist[]=array('lifecyclestatus'=>$status['lifecyclestatus']);}
 
 $partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuslist);
 
 $pim->logBackgroundjobEvent($jobid, 'Part Categories:'.implode(',',$partcategories));
 
 $outputpath=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 } 

 $uniquefilenames=array();
 $hitlistfiles=array();
 $uncompressedbytetotal=0;
 $errorcount=0;
 $zipfilename= random_int(1000000, 9999999).'.zip';
 $tempdirname= random_int(1000000, 9999999);
 $totalretrycount=0;
 $retrycount=0;
 
 $shellresult= shell_exec('mkdir '.$outputpath.$tempdirname);
 $pim->logBackgroundjobEvent($jobid, 'Profile: '.$profilename);
 $pim->logBackgroundjobEvent($jobid, 'Created temp directory ('.$outputpath.$tempdirname.') '.$shellresult);
 
 foreach($partnumbers as $partnumberindex=>$partnumber)
 {     
  $digialassetconnections=$assets->getAssetsConnectedToPart($partnumber,true); // second arg is "$excludenonpublic". Setting it to true will cause only public=1 records to be returned
  // get assettags for filtering asset list to only tags that this profile wants
  $profileassettags=$pim->getAssettagsForReceiverprofile($profile['id']); //$assettags[]=array('id'=>$row['id'],'assettagid'=>$row['assettagid'],'tagtext'=>$row['tagtext']);
  
  if($digialassetconnections && count($digialassetconnections))
  {
   foreach($digialassetconnections as $digitalassetconnection)
   {
    $digitalassetrecords=$assets->getAssetRecordsByAssetid($digitalassetconnection['assetid']);
    foreach($digitalassetrecords as $digitalassetrecord)
    {        
     $assettags=$assets->getAssettagsForAsset($digitalassetrecord['assetid']); //$tags[]=array('id'=>$row['id'],'assettagid'=>$row['assettagid'],'tagtext'=>$row['tagtext']);
     // short (continue) the loop if this asset's tag list dosn't include any tags in the profile's list
     $foundtag=false; $firstmatchedtagtext='';
     foreach($assettags as $assettag)
     {
      foreach($profileassettags as $profileassettag)
      {
       if($profileassettag['tagtext']==$assettag['tagtext']){$foundtag=true; $firstmatchedtagtext=$assettag['tagtext']; break;}         
      }
     }
     
     if(!$foundtag){continue;}

     $filename=$digitalassetrecord['filename'];
     $assetid=$digitalassetrecord['assetid'];
     $assetype=$digitalassetconnection['assettypecode'];
     $filetype=$digitalassetrecord['fileType'];
     $uri=$digitalassetrecord['uri'];
     $fileHashMD5=$digitalassetrecord['fileHashMD5'];
     
     if(in_array($filename,$uniquefilenames)){continue;} // skip file if it has already been added
               
     if($uri!='' && in_array($filetype,['JPG','PNG','PDF','BMP','TIF']))
     { 
      $fixedescapeduri = str_replace(['%2F', '%3A'], ['/', ':'], urlencode($uri));
            
      $retrycount=0;
      while($retrycount<=5)
      {
       $assetfilecontents = file_get_contents($fixedescapeduri);
       $downloadsize=strlen($assetfilecontents);
       if($downloadsize>0){break;} // move on if the download has size
       $pim->logBackgroundjobEvent($jobid, $filename.': download failed - retrying');
       $retrycount++;
       sleep(5);
      }
      if($retrycount>0){$totalretrycount+=$retrycount;}
            
      $downloadhash=md5($assetfilecontents);
      if($downloadsize)
      {// downloaded something. If we were being strict, a size and hash check would be wise here

       if($verbosity>0){$pim->logBackgroundjobEvent($jobid, 'Downloaded '.$filename.' - size:'.$downloadsize.', md5:'.$downloadhash);}
          
       if($verifyhashes && $downloadhash!=$fileHashMD5)
       {// local official hash does not agree with the content just downloaded
        $pim->logBackgroundjobEvent($jobid, $filename.' - md5 of download disagrees with local record');
        $errorcount++;           
       }          
          
       $uniquefilenames[]=$filename;
       
       if(file_put_contents($outputpath.$tempdirname.'/'.$filename,$assetfilecontents)===false)
       {// local write to the temp folder failed
        $pim->logBackgroundjobEvent($jobid, $filename.' - local save to '.$outputpath.$tempdirname.'/'.$filename.' failed');
        $errorcount++;
       }
       else
       {// successful local write of the file
          
        // add the file to the hitlist for later delete
        $hitlistfiles[]=$outputpath.$tempdirname.'/'.$filename;
        $uncompressedbytetotal+=$downloadsize;
       }
       
      }
      else
      {// download size is 0
       $pim->logBackgroundjobEvent($jobid, $filename.': empty download');
       $errorcount++;
      }
     }
    }
   }
  }
  $pim->updateBackgroundjobStatus($jobid, 'running', round(90*($partnumberindex/count($partnumbers)),0));
 }
 
 // zip all local files from temp directory
 $shellresult= shell_exec('zip -q -j '.$outputpath.$zipfilename.' '.$outputpath.$tempdirname.'/*');
 if(strlen($shellresult)>0)
 {
  $pim->logBackgroundjobEvent($jobid, 'zipped all files in '.$outputpath.$tempdirname.' into '.$outputpath.$zipfilename.' '.$shellresult);
 }
 
 // delete local files in temp dir by hitlist
 foreach($hitlistfiles as $hitlistfile)
 {
  $shellresult= shell_exec('rm -f "'.$hitlistfile.'"');
  if(strlen($shellresult)>0)
  {
   $pim->logBackgroundjobEvent($jobid, $filename.' - local delete from '.$hitlistfile.' '.$shellresult);
  }
 }

 // remove temp dir 
 $shellresult= shell_exec('rmdir '.$outputpath.$tempdirname);
 if(strlen($shellresult)>0)
 {
  $pim->logBackgroundjobEvent($jobid, 'temp directory ('.$outputpath.$tempdirname.') delete '.$shellresult);
 }
 
 
 if($errorcount==0)
 {
  $pim->updateBackgroundjobDone($jobid, 'complete', date('Y-m-d H:i:s'));
  $pim->updateBackgroundjobClientfilename($jobid,$zipfilename);
 }
 else
 {
  $pim->updateBackgroundjobDone($jobid, 'failed', date('Y-m-d H:i:s'));     
 }
 
 $pim->updateBackgroundjobOutputfile($jobid, $outputpath.$zipfilename);

 $runtime=time()-$starttime;
 $pim->logBackgroundjobEvent($jobid, 'processed '.count($uniquefilenames).' files for '.count($partnumbers).' partnumbers in '.$runtime.' seconds. Total uncomressed size: '.number_format($uncompressedbytetotal/1000000,0,'.',',').'MB; Error count: '.$errorcount.'; retries:'.$totalretrycount);
 $logs->logSystemEvent('assetbundle', 0 , $profilename.' - bundled '.count($uniquefilenames).' files for '.count($partnumbers).' partnumbers in '.$runtime.' seconds. Total uncomressed size: '.number_format($uncompressedbytetotal/1000000,0,'.',',').'MB; Error count:'.$errorcount.'; retries:'.$totalretrycount); 
}
else
{
 //echo"no jobs pending\r\n";    
}
