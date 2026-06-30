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
$jobs=$pim->getBackgroundjobs('PairPartBulk','started');

if(count($jobs))
{
 ini_set('memory_limit','1000M');
 include_once(__DIR__.'/class/logsClass.php');
 include_once(__DIR__.'/class/vcdbClass.php');
 include_once(__DIR__.'/class/pcdbClass.php');
 include_once(__DIR__.'/class/qdbClass.php');
 include_once(__DIR__.'/class/pricingClass.php');
 include_once(__DIR__.'/class/configGetClass.php');
 include_once(__DIR__.'/class/XLSXWriterClass.php');

 $starttime=time();

 $logs=new logs();
 $vcdb=new vcdb();
 $pcdb=new pcdb();
 $qdb=new qdb();
 $pricing=new pricing();
 $configGet = new configGet();
 $writer = new XLSXWriter();
 
 $existinglocks=$pim->getLocksByType('PairPartBulk');
 if(count($existinglocks))
 {
  $logs->logSystemEvent('PairPartBulk', 0, 'PairPartBulk processor found lock record (id:'.$existinglocks[0]['id'].') and declined to run');
  exit; 
 }
 $mylockid=$pim->addLock('PairPartBulk', 'pid:'. getmypid());
  
 $writer->setAuthor('SandPIM');
 $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Match 1 Part'=>'string','Match 1 Share'=>'#,##0','Match 2'=>'string','Match 2 Share'=>'#,##0','Match 3'=>'string','Match 3 Share'=>'#,##0','Match 4'=>'string','Match 4 Share'=>'#,##0','Match 5'=>'string','Match 6'=>'string','Match 7'=>'string','Match 8'=>'string'), array('widths'=>array(20,15,13,15,13,15,13,15,13,15,15,15,15),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
 $tabbedoutputrecords=[];
 
 $positionmode='same';
 $pairwithparttypeid=0;
 $partcategories=[];
 $viodisplaymode='percentage';
 
 
 $filename=$jobs[0]['outputfile'];
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
 if(array_key_exists('positonmode', $parameters)){$positionmode=$parameters['positonmode'];} 
 if(array_key_exists('pairwithparttypeid', $parameters)){$pairwithparttypeid=intval($parameters['pairwithparttypeid']);}
 if(array_key_exists('viogeography', $parameters)){$viogeography=$parameters['viogeography'];}
 if(array_key_exists('vioyearquarter', $parameters)){$vioyearquarter=$parameters['vioyearquarter'];}
 if(array_key_exists('viodisplaymode', $parameters)){$viodisplaymode=$parameters['viodisplaymode'];}
 
 if(array_key_exists('deliverygroup', $parameters))
 {
  $partcategoriestemp=$pim->getDeliverygroupPartcategories(intval($parameters['deliverygroup']));    
  foreach($partcategoriestemp as $p)
  {
   $partcategories[]=$p['id'];
  }
 }
 
 $pim->logBackgroundjobEvent($jobid, 'Input Categories: '.implode(',',$partcategories));
 
 $partnumbers=[]; 
 if(array_key_exists('partnumbers', $parameters)){$partnumbers=explode("\t", base64_decode($parameters['partnumbers']));}

 foreach($partnumbers as $partnumberindex=>$partnumber)
 {
  $outputs=[];
  $leftapps=$pim->getAppsByPartnumber($partnumber);
  //$digitassets=$asset->getAssetsConnectedToPart($partnumber);
  //$leftimageuri=false; foreach($digitassets as $digitalasset){if($digitalasset['assettypecode']=='P04'){$leftimageuri=$digitalasset['uri']; break;}}
  //$packages=$packaging->getPackagesByPartnumber($partnumber);
  //$leftpackage=false; foreach($packages as $package){if($package['packageuom']=='EA'){$leftpackage=$package; break;}}
 
 
  $basevidspositions=array();
  foreach($leftapps as $leftapp)
  {
   if(array_key_exists($leftapp['basevehicleid'], $basevidspositions))
   {
    if(!in_array($leftapp['positionid'], $basevidspositions[$leftapp['basevehicleid']]))
    {
     $basevidspositions[$leftapp['basevehicleid']][]=$leftapp['positionid'];   
    }           
   }
   else
   {
    $basevidspositions[$leftapp['basevehicleid']][]=$leftapp['positionid'];         
   }
  }
  
  $lefthashes=array();
  foreach($leftapps as $app)
  {
   $niceattributes=array();
   foreach($app['attributes'] as $appattribute)
   {
    switch ($appattribute['type']) 
    {
     case 'vcdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']); break;
     case 'qdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']);                 break;
     case 'note': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']); break;
     default:break;
    } 
   }

   $nicefitmentarray = array(); foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
   $nicefitmentstring=implode('; ', $nicefitmentarray);
   $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
  
   if($positionmode=='same')
   {
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$pcdb->positionName($app['positionid'])."\t".$nicefitmentstring."\r\n";
   }
   else
   {
    $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring."\r\n";
   }
   $lefthashes[]= md5($linestring);
  }


  $partkeyedcandidateapps=array();
 
  // disqualify parts not in "available" status or having no P04 assets or not of "pairwith" parttype
  $qualifyingpartstemp=array(); 
  foreach($basevidspositions as $basevid=>$positions)
  {
   $appstemp=$pim->getAppsByBasevehicleid($basevid, $partcategories);
   foreach($positions as $position)
   {
    foreach($appstemp as $app)
    {
     if(!in_array($app['partnumber'], $qualifyingpartstemp)){$qualifyingpartstemp[]=$app['partnumber'];}
    }
   }
  }

  $qualifyingparts=array(); 
  foreach($qualifyingpartstemp as $qualifyingpart)
  {
   $parttemp=$pim->getPart($qualifyingpart);
   if($parttemp['lifecyclestatus']=='2' && $parttemp['parttypeid']==$pairwithparttypeid)
   {
    //$digitassets=$asset->getAssetsConnectedToPart($qualifyingpart, true);
    //$foundprimaryimage=false; foreach($digitassets as $digitalasset){if($digitalasset['assettypecode']=='P04'){$foundprimaryimage=true; break;}}
    //if($foundprimaryimage){$qualifyingparts[]= $qualifyingpart;}
    $qualifyingparts[]= $qualifyingpart;       
   }
  }
 
 
  foreach($basevidspositions as $basevid=>$positions)
  {
   $appstemp=$pim->getAppsByBasevehicleid($basevid, $partcategories);
   foreach($positions as $position)
   {
    foreach($appstemp as $apptemp)
    {
     if($positionmode=='same')
     {// we are interested in stuff at the same position as the input part
      if($apptemp['positionid']==$position && $apptemp['parttypeid']==$pairwithparttypeid && $apptemp['partnumber']!=$partnumber)
      {
       $partkeyedcandidateapps[$apptemp['partnumber']][]=$apptemp;
      }
     }
     else
     {// we are interested in stuff at other positions than the given part
      if($apptemp['positionid']!=$position && $apptemp['parttypeid']==$pairwithparttypeid && $apptemp['partnumber']!=$partnumber)
      {
       $partkeyedcandidateapps[$apptemp['partnumber']][]=$apptemp;
      }        
     }
    }
   }   
  }
 
  $finalcandidateapplines=array();
  $tempscores=array();

  foreach($partkeyedcandidateapps as $candidatepartnumber=>$apps)
  {
   foreach($apps as $app)
   {
    $niceattributes=array();
    foreach($app['attributes'] as $appattribute)
    {
     switch ($appattribute['type']) 
     {
      case 'vcdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $vcdb->niceVCdbAttributePair($appattribute), 'cosmetic' => $appattribute['cosmetic']); break;
      case 'qdb': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $qdb->qualifierText($appattribute['name'], explode('~', str_replace('|','',$appattribute['value']))), 'cosmetic' => $appattribute['cosmetic']); break;
      case 'note': $niceattributes[] = array('sequence' => $appattribute['sequence'], 'text' => $appattribute['value'], 'cosmetic' => $appattribute['cosmetic']); break;
      default:break;
     } 
    }
    $nicefitmentarray = array(); foreach ($niceattributes as $niceattribute){$nicefitmentarray[] = $niceattribute['text'];}
    $nicefitmentstring=implode('; ', $nicefitmentarray);

    $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);

    if($positionmode=='same')
    {
     $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$pcdb->positionName($app['positionid'])."\t".$nicefitmentstring."\r\n";
    }
    else
    {
     $linestring=$mmy['year']."\t".$mmy['makename']."\t".$mmy['modelname']."\t".$nicefitmentstring."\r\n";
    }
    if(!in_array(md5($linestring), $lefthashes)){continue;}

    $finalcandidateapplines[$candidatepartnumber][]=$linestring;

    $vio=$pim->appVIOexperian($app['id'], $viogeography, $vioyearquarter, $app['attributes']);
    if(array_key_exists($candidatepartnumber, $tempscores))
    {
     $tempscores[$candidatepartnumber]+=$vio;
    }
    else
    {
     $tempscores[$candidatepartnumber]=$vio;
    }
   }
  }

  foreach($finalcandidateapplines as $candidatepartnumber=>$fitmentlines)
  {
   $outputs[]=array('partnumber'=>$candidatepartnumber,'fitmentlines'=>$fitmentlines,'score'=>$tempscores[$candidatepartnumber],'asset'=>'');
  }

  $scoreindex=array(); $totalscores=1;
  foreach($outputs as $rowid=>$output)
  {
   $scoreindex[$rowid]=$output['score'];
   $totalscores+=$output['score'];
  }
 
  array_multisort($scoreindex,SORT_DESC,$outputs);
  
  $matchedpart1=''; $matchedpart1vio='';
  $matchedpart2=''; $matchedpart2vio='';
  $matchedpart3=''; $matchedpart3vio='';
  $matchedpart4=''; $matchedpart4vio='';
  $matchedpart5=''; $matchedpart5vio='';
  $matchedpart6=''; $matchedpart6vio='';
  $matchedpart7=''; $matchedpart7vio='';
  $matchedpart8=''; $matchedpart8vio='';
  
  if(count($outputs)>0){$matchedpart1=$outputs[0]['partnumber'];}
  if(count($outputs)>1){$matchedpart2=$outputs[1]['partnumber'];}
  if(count($outputs)>2){$matchedpart3=$outputs[2]['partnumber'];}
  if(count($outputs)>2){$matchedpart3=$outputs[2]['partnumber'];}
  if(count($outputs)>3){$matchedpart4=$outputs[3]['partnumber'];}
  if(count($outputs)>4){$matchedpart5=$outputs[4]['partnumber'];}
  if(count($outputs)>5){$matchedpart6=$outputs[5]['partnumber'];}
  if(count($outputs)>6){$matchedpart7=$outputs[6]['partnumber'];}
  if(count($outputs)>7){$matchedpart8=$outputs[7]['partnumber'];}

  
  if($viodisplaymode=='percentage')
  {
   if(count($outputs)>0){$matchedpart1vio=round(($outputs[0]['score']/$totalscores)*100,0);}
   if(count($outputs)>1){$matchedpart2vio=round(($outputs[1]['score']/$totalscores)*100,0);}
   if(count($outputs)>2){$matchedpart3vio=round(($outputs[2]['score']/$totalscores)*100,0);}
   if(count($outputs)>3){$matchedpart4vio=round(($outputs[3]['score']/$totalscores)*100,0);}
   if(count($outputs)>4){$matchedpart5vio=round(($outputs[4]['score']/$totalscores)*100,0);}
   if(count($outputs)>5){$matchedpart6vio=round(($outputs[5]['score']/$totalscores)*100,0);}
   if(count($outputs)>6){$matchedpart7vio=round(($outputs[6]['score']/$totalscores)*100,0);}
   if(count($outputs)>7){$matchedpart8vio=round(($outputs[7]['score']/$totalscores)*100,0);}
  }
  
  if($viodisplaymode=='actual')
  {
   if(count($outputs)>0){$matchedpart1vio=$outputs[0]['score'];}
   if(count($outputs)>1){$matchedpart2vio=$outputs[1]['score'];}
   if(count($outputs)>2){$matchedpart3vio=$outputs[2]['score'];}
   if(count($outputs)>3){$matchedpart4vio=$outputs[3]['score'];}
   if(count($outputs)>4){$matchedpart5vio=$outputs[4]['score'];}
   if(count($outputs)>5){$matchedpart6vio=$outputs[5]['score'];}
   if(count($outputs)>6){$matchedpart7vio=$outputs[6]['score'];}
   if(count($outputs)>7){$matchedpart8vio=$outputs[7]['score'];}
  }
  
  
  
  
  // write the output row to spreadsheet (choose the the first 8 results) 
  $tabbedoutputrecords[]=$partnumber."\t".$matchedpart1."\t".$matchedpart1vio."\t".$matchedpart2."\t".$matchedpart2vio."\t".$matchedpart3."\t".$matchedpart3vio."\t".$matchedpart4."\t".$matchedpart4vio."\t".$matchedpart5."\t".$matchedpart6."\t".$matchedpart7."\t".$matchedpart8;
 
  $pim->logBackgroundjobEvent($jobid, 'Processed: '.$partnumber.', found '.count($outputs).' matches');

  
  $pim->updateBackgroundjobStatus($jobid, 'running', round((($partnumberindex+1)/count($partnumbers))*100,0));
 }
 
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

 $runtime=time()-$starttime;
 
 if($writeresult)
 {
  //echo 'output file created ('.$writeresult.' bytes)';
  $pim->updateBackgroundjobDone($jobid,'complete',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'Part matchmaker file ['.$filename.'] created containing '.count($partnumbers).' parts');
  $logs->logSystemEvent('Export', 0, 'Part matchmaker file ['.$filename.'] (jobid:'.$jobid.') exported by background processing; parts:'.count($partnumbers).' in '.$runtime.' seconds');
 }
 else
 {  // writing the output xlsx file failed
  //echo 'output file write failed';
  $pim->updateBackgroundjobDone($jobid,'failed',date('Y-m-d H:i:s'));
  $pim->logBackgroundjobEvent($jobid, 'file write failed ['.$filename.']' );
  $logs->logSystemEvent('Export', 0, 'Part matchmaker file ['.$filename.'] (jobid:'.$jobid.') export failed (write permission denied) during background processing; parts:'.count($partnumbers));
 }

 $pim->removeLockById($mylockid);
}
else
{
 echo "no jobs pending\r\n";    
}