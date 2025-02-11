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
 ini_set('memory_limit','1000M');
 include_once(__DIR__.'/class/logsClass.php');
 include_once(__DIR__.'/class/vcdbClass.php');
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/packagingClass.php');
 include_once(__DIR__.'/class/pricingClass.php');
 include_once(__DIR__.'/class/configGetClass.php');
 include_once(__DIR__.'/class/XLSXWriterClass.php');

 $logs=new logs();
 $vcdb=new vcdb();
 $pcdb=new pcdb();
 $packaging = new packaging();
 $pricing=new pricing();
 $configGet = new configGet();
 $writer = new XLSXWriter();

 $viogeography=$configGet->getConfigValue('VIOdefaultGeography');
 $vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

 $forcesummaryupdate=true;
  
 $file_name=$jobs[0]['outputfile'];
 $jobid=$jobs[0]['id'];
 $pim->updateBackgroundjobRunning($jobid, date('Y-m-d H:i:s'));
 
 $parameters=array();
 $parameterbits=explode(';',$jobs[0]['parameters']);
 foreach($parameterbits as $parameterbit)
 {
  $temp=explode(':',$parameterbit); if(count($temp)==2){$parameters[$temp[0]]=$temp[1];}
 }

 $pricesheetnumber=''; $pricesheetcurrency='?'; $pricesheetdescription='Price';
 if(array_key_exists('pricesheetnumber', $parameters))
 {     
  $pricesheetnumber=$parameters['pricesheetnumber'];
  $pricesheet=$pricing->getPricesheet($pricesheetnumber);
  if($pricesheet)
  {
   $pricesheetdescription=$pricesheet['description'].' ('.$pricesheet['currency'].')';
   $pricesheetcurrency=$pricesheet['currency'];
  }
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
 $temppartcount=0; $processedcount=0;
 
 $mmycache=array();
 
  
 
 
 
 
 foreach($partnumbers as $partnumber) 
 {
  if($part=$pim->getPart($partnumber))
  {
    $temppartcount++; $processedcount++;
    $vio=$pim->partVIOtotal($part['partnumber'], $viogeography, $vioyearquarter);
    $summarytemp=$pim->getAppSummary($part['partnumber']);  
    if($summarytemp['age']>30 || $summarytemp['age']<0 || $forcesummaryupdate)
    {// existing summary is stale or missing - recapture it
           
     $rawapps=$pim->getAppsByPartnumber($part['partnumber'],true);
    
     $apps=array();
     $makesindex=array(); $modelsindex=array(); $yearsindex=array();
     
     foreach($rawapps as $rowid=>$rawapp)
     {
      if(array_key_exists($rawapp['basevehicleid'],$mmycache))
      {
       $mmy=$mmycache[$rawapp['basevehicleid']];           
      }
      else
      {
       $mmy=$vcdb->getMMYforBasevehicleid($rawapp['basevehicleid']);
       $mmycache[$rawapp['basevehicleid']]=$mmy;           
      }         
      
      $apps[]=array('makename'=>$mmy['makename'],'modelname'=>$mmy['modelname'],'year'=>$mmy['year']);
      $makesindex[$rowid]=$mmy['makename'];
      $modelsindex[$rowid]=$mmy['modelname'];
      $yearsindex[$rowid]=$mmy['year'];
     }
     
     array_multisort($makesindex,SORT_ASC,$modelsindex,SORT_ASC,$yearsindex,SORT_ASC,$apps);
     
     
     
     $temp=array(); $oldestyear=9999; $newestyear=0;
     foreach($apps as $app)
     {
      $appyear=intval($app['year']);
      
      
      if($appyear>$newestyear){$newestyear=$appyear;}
      if($appyear<$oldestyear){$oldestyear=$appyear;}
      
      $key=$app['makename'].'_'.$app['modelname'];
      if(array_key_exists($key, $temp))
      {// make_model exists in the array. See if year is compatible with an existing entry

       $found=false;
          
       for($i=0; $i<=(count($temp[$key])-1); $i++)
       {// look inside each existing year range for this make/mode entry  
        if(($appyear>=($temp[$key][$i]['start'])) && ($appyear<=($temp[$key][$i]['end'])))
        {// app is inside existing year range.
         $found=true; break;
        }        
       }
       
       if(!$found)
       {// app did not find a home inside an existing uear range - now test the edges   
        for($i=0; $i<=(count($temp[$key])-1); $i++)
        {// look inside each existing year range for this make/mode entry  
         if(($appyear+1)==($temp[$key][$i]['start']))
         {// app is contiguous to the low edge of existing year range.
          $temp[$key][$i]['start']=$appyear;
          $found=true; break;
         }
        }
       }

       if(!$found)
       {
        for($i=0; $i<=(count($temp[$key])-1); $i++)
        {// look inside each existing year range for this make/mode entry  
         if(($appyear-1)==($temp[$key][$i]['end']))
         {// app is contiguous to the low edge of existing year range.
          $temp[$key][$i]['end']=$appyear;
          $found=true; break;
         }
        }
       }
       
       if(!$found)
       {// current app found no home in or on the edge of an existing range - add a home for it
        $temp[$key][]=array('start'=>$appyear,'end'=>$appyear,'status'=>1);
        $found=true;
       }
      }
      else
      {// make_model does not already exist in the array - add it and set both the start and end to this apps year
       $temp[$key][]=array('start'=>$appyear,'end'=>$appyear,'status'=>1);
      }      
     }
     // all apps consumed for current item
     
     
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
     
     $summary=implode(', ',$nicelist);
     $pim->updateAppSummary($part['partnumber'], $summary, $oldestyear, $newestyear);
    }
    else
    {//existing summary is not stale
        
     $summary=$summarytemp['summary'];
     $oldestyear=$summarytemp['firstyear'];
     $newestyear=$summarytemp['lastyear'];
    }
    
    // $summary contains meaningful data (either fresh or cahced)    
    $balance=$pim->getPartBalance($part['partnumber']);
    $qoh=0; $amd=0;
    if($balance){$qoh=$balance['qoh']; $amd=$balance['amd'];}
    
    $partpackages=$packaging->getPackagesByPartnumber($part['partnumber']);  
    $caseqty=0; $unitweight=0; $unitweightuom='';
    foreach($partpackages as $partpackage)
    {
        if($partpackage['packageuom']=='EA'){$caseqty=$partpackage['weight']; $unitweightuom=$partpackage['weightsuom'];}
        if($partpackage['packageuom']=='CA'){$caseqty=$partpackage['innerquantity'];}
    }
   
    $pricevalue=0;
    if($pricesheetnumber!='')
    {
     $pricerecords=$pricing->getPricesByPartnumber($part['partnumber'],$pricesheetnumber);
     if(count($pricerecords)>0){$pricevalue=$pricerecords[0]['amount'];}
    }
    
    $ucc14='';
    if(strlen($part['GTIN'])==12)
    {
     $ucc14checkdigit=$pim->gtinCheckDigit('10'.$part['GTIN']);
     $ucc14='10'.substr($part['GTIN'],0,-1).$ucc14checkdigit;
    }
    
    $firststockeddate=$part['firststockedDate'];
    
    $tabbedoutputrecord=$part['partnumber']."\t".$pim->partCategoryName($part['partcategory'])."\t".$part['GTIN']."\t".$ucc14."\t".$pcdb->parttypeName($part['parttypeid'])."\t".$part['firststockedDate']."\t".$pcdb->lifeCycleCodeDescription($part['lifecyclestatus'])."\t".$part['replacedby']."\t".$pricevalue."\t".$qoh."\t".$amd."\t".$caseqty."\t".$vio."\t".$oldestyear."\t".$newestyear."\t".$summary;
    $tabbedoutputrecords[]=$tabbedoutputrecord;
    $tabbedoutput.=$tabbedoutputrecord."\r\n";
  
    if($temppartcount>=100)
    {
     $temppartcount=0;
     $pim->logBackgroundjobEvent($jobid, 'processed '.$processedcount.' parts');        
    }
    
  }
 }
 
 
 
 
 $writer->setAuthor('SandPIM');
 $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','UPC'=>'string','UCC14'=>'string','Part Type'=>'string','First Stocked'=>'string','Status'=>'string','Replaced By'=>'string',$pricesheetdescription=>'0.00','QoH'=>'#,##0','AMD'=>'#,##0.0','Case Qty'=>'integer','VIO ('.$viogeography.' '.$vioyearquarter.')'=>'#,##0','First model-year'=>'integer','Last model-year'=>'integer','Applications'=>'string'), array('widths'=>array(12,25,13,14,20,11,15,11,10,10,10,8,16,14,14,150),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
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
