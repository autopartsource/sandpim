<?php
/*
 * intended to be executed from the command-line be a cron call ("php processApplicationGuideExport.php")
 * on a cycle (likely every 5 or 10 minutes). It will query the db for the oldest job that 
 * is status "started" and execute it.
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
$jobs=$pim->getBackgroundjobs('BuyersGuideExport','started');

if(count($jobs))
{
 ini_set('memory_limit','6000M');
 include_once(__DIR__.'/class/logsClass.php');
 include_once(__DIR__.'/class/vcdbClass.php');
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/XLSXWriterClass.php');

 $logs=new logs();
 $vcdb=new vcdb();
 $pcdb=new pcdb();
 $writer = new XLSXWriter();

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
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories);
 
 $filename=$jobs[0]['outputfile'];
 $profileelements=explode(';',$profiledata);
 $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 }
 
 $tabbedoutput='';
  
 foreach($partnumbers as $partnumber) 
 {
  if($part=$pim->getPart($partnumber))
  {
   $firstyear=9999; $lastyear=0;   
   $apps=$pim->getAppsByPartnumber($partnumber);
    
   $temp=array();
   foreach($apps as $app)
   {
    $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
    $key=$mmy['makename'].'_'.$mmy['modelname'];
    if($mmy['year'] < $firstyear){$firstyear=$mmy['year'];}
    if($mmy['year'] > $lastyear){$lastyear=$mmy['year'];}
    if(array_key_exists($key, $temp))
    {// make_model exists in the array. See if year is compatible with an existing entry
         
     for($i=0; $i<=count($temp[$key])-1; $i++)
     {// look inside each existing year range for this make/mode entry
      if($mmy['year']<$temp[$key][$i]['start'] || $mmy['year']>$temp[$key][$i]['end'])
      {// app is outside existing year range. see if it is contiguous with an existing range
       $found=false;
      
       if($mmy['year']==($temp[$key][$i]['start'])-1)
       {// expand the range down
        $temp[$key][$i]['start']=$mmy['year']; $found=true;
       }          
       if($mmy['year']==($temp[$key][$i]['end'])+1)
       {// expand the range up
        $temp[$key][$i]['end']=$mmy['year']; $found=true;
       }          
        
       if(!$found)
       {
        $temp[$key][]=array('start'=>$mmy['year'],'end'=>$mmy['year']);
       }
      }
     }   
    }
    else
    {// make_model does not already exist in the array - add it and set both the start and end to this apps year
     $temp[$key][]=array('start'=>$mmy['year'],'end'=>$mmy['year']);
    }
   }
   unset($apps);
   
   $nicelist=array();
   ksort($temp);
   foreach($temp as $makemodel=>$yearranges)
   {
    $makemodelbits=explode('_',$makemodel);
    $make=$makemodelbits[0]; $model=$makemodelbits[1];
   
    foreach($yearranges as $yearrange)
    {
     if($yearrange['start']==$yearrange['end'])
     {// range is only one year wide - render as a single year (ex: "2000")
      $nicelist[]=$make.' '.$model.' ('.$yearrange['start'].')';
     }
     else 
     {// range is wider than a single year - render as a dashed ranges (ex: "2015-2019")
      $nicelist[]=$make.' '.$model.' ('.$yearrange['start'].'-'.$yearrange['end'].')';         
     }
    }
   }
   
   unset($temp);

     
   $summary=implode(', ',$nicelist);
   $pim->updateAppSummary($part['partnumber'], $summary,$firstyear,$lastyear);
        
   $tabbedoutputrecord=$partnumber."\t".$pim->partCategoryName($part['partcategory'])."\t".$pcdb->parttypeName($part['parttypeid'])."\t".$pcdb->lifeCycleCodeDescription($part['lifecyclestatus'])."\t".$summary;
   $tabbedoutputrecords[]=$tabbedoutputrecord;
   $tabbedoutput.=$tabbedoutputrecord."\r\n";
  }
  gc_collect_cycles();
 }
 
 $writer->setAuthor('SandPIM');
 $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','Applications'=>'string'), array('widths'=>array(18,20,13,30,150),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
 foreach($tabbedoutputrecords as $tabbedoutputrecord)
 {
  $row=explode("\t",$tabbedoutputrecord);
  $writer->writeSheetRow('Sheet1', $row);
 }

 $xlsxdata=$writer->writeToString();
 
 $writeresult=false;
 if($fh=fopen($filename, 'w'))
 {
  $writeresult=fwrite($fh, $xlsxdata);
  fclose($fh);
 }

 if($writeresult)
 {
  //echo 'output file created ('.$writeresult.' bytes)';
  $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'Application Guide file ['.$filename.'] created containing '.count($partnumbers).' parts');
  $logs->logSystemEvent('Export', 0, 'Application Guide file ['.$filename.'] (jobid:'.$jobid.') exported by background processing; parts:'.count($partnumbers));
 }
 else
 {  // writing the output xlsx file failed
  //echo 'output file write failed';
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
  $logs->logSystemEvent('Export', 0, 'Application Guide file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied) during background processing; parts:'.count($partnumbers));
 }
}
else
{
 echo "no jobs pending\r\n";    
}
?>
