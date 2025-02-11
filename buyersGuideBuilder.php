<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/configGetClass.php');
include_once('./class/XLSXWriterClass.php');

$navCategory = 'utilities';

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'buyersGuideBuilder.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

ini_set('memory_limit','1000M');



$logs = new logs();
$vcdb = new vcdb();
$pcdb = new pcdb();
$packaging = new packaging();
$configGet = new configGet();

$tabbedoutput='';
$tabbedoutputrecords=array();
$streamXLSX=false;
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');

$forcesummaryupdate=true;

if(isset($_POST['submit']) && strlen($_POST['input'])>0) 
{
 
 $input = $_POST['input'];
 $records = explode("\r\n", $input);
 $tabbedoutput="Partnumber\tCategory\tUPC\tPart Type\tLifecycle Status\tReplaced By\tQoH\tAMD\tPackages\tApplications\r\n";

 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if(count($fields)>=1)
  {   
   if($part=$pim->getPart(trim($fields[0])))
   {
    //$vio=$pim->partVIOexperian($part['partnumber'], $viogeography, $vioyearquarter);
    $vio=$pim->partVIOtotal($part['partnumber'], $viogeography, $vioyearquarter);
    $meanyear=$pim->partVIOmeanYear($part['partnumber'], $viogeography, $vioyearquarter);
    $viogrowthtrend=$pim->partVIOgrowthTrend($part['partnumber'], $viogeography, $vioyearquarter);
    $summarytemp=$pim->getAppSummary($part['partnumber']);  
    if($summarytemp['age']>30 || $summarytemp['age']<0 || $forcesummaryupdate)
    {// existing summary is stale or missing - recapture it
           
     $rawapps=$pim->getAppsByPartnumber($fields[0]);
    
     $apps=array();
     $makesindex=array(); $modelsindex=array(); $yearsindex=array();
     
     foreach($rawapps as $rowid=>$rawapp)
     {
      $mmy=$vcdb->getMMYforBasevehicleid($rawapp['basevehicleid']);
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
    
    $nicepackagestring='';
    $partpackages=$packaging->getPackagesByPartnumber($part['partnumber']);
    
    if(count($partpackages))
    {
     $nicepackagestring=$partpackages[0]['nicepackage'];
    }
        
    $tabbedoutputrecord=$fields[0]."\t".$pim->partCategoryName($part['partcategory'])."\t".$part['GTIN']."\t".$pcdb->parttypeName($part['parttypeid'])."\t".$pcdb->lifeCycleCodeDescription($part['lifecyclestatus'])."\t".$part['replacedby']."\t".$qoh."\t".$amd."\t".$nicepackagestring."\t".$vio."\t".$oldestyear."\t".$meanyear."\t".$newestyear."\t".$viogrowthtrend."\t".$summary;
    $tabbedoutputrecords[]=$tabbedoutputrecord;
    $tabbedoutput.=$tabbedoutputrecord."\r\n";
   }
   else
   {// item was not found
    if(strlen(trim($fields[0]))>0)
    {
     $tabbedoutputrecords[]=trim($fields[0]).'*';
     $tabbedoutput.=trim($fields[0])."*\r\n";
    }  
   }
  }
 }
 
 if(isset($_POST['renderxlsx']))
 {
  $writer = new XLSXWriter();
  $writer->setAuthor('SandPIM');
  $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','UPC'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','Replaced By'=>'string','QoH'=>'#,##0','AMD'=>'#,##0.0','Packages'=>'string','VIO ('.$viogeography.' '.$vioyearquarter.')'=>'#,##0','First model-year'=>'integer','Mean Model-Year'=>'integer','Last model-year'=>'integer','VIO Trend %'=>'0.00','Applications'=>'string'), array('widths'=>array(18,20,13,30,20,18,10,10,20,16,14,15,14,11,150),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
  foreach($tabbedoutputrecords as $tabbedoutputrecord)
  {
   $row=explode("\t",$tabbedoutputrecord);
   $writer->writeSheetRow('Sheet1', $row);
  }

  $xlsxdata=$writer->writeToString();
  $streamXLSX=true; 
 }
 
 $logs->logSystemEvent('UTILITIES', $_SESSION['userid'], 'Buyers guide builder - '.count($records).' parts');
}

if($streamXLSX)
{
 $filename='buyersguide_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;    
}
else
{?>
<!DOCTYPE html>
<html>
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-start">Build Buyer's Guide for a list of partnumbers</h3>

                        <div class="card-body">
                            <form method="post">
                                <div class="alert alert-secondary" role="alert">Partnumbers (one per line)</div>
                                <div><textarea name="input" style="width:100%;height:200px;"><?php echo $tabbedoutput;?></textarea></div>
                                <div><input type="checkbox" id="renderxlsx" name="renderxlsx" checked="checked"/><label for="renderxlsx">Download As Excel Spreadsheet</label></div>


                                <div style="padding:5px;"><input type="submit" name="submit" value="Generate"/></div>
                            </form>
                        </div>
                    </div>
                    
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->

        <!-- Footer -->
<?php include('./includes/footer.php'); ?>
    </body>
</html>
<?php }?>
