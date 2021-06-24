<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');

$navCategory = 'utilities';


session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim();
$logs = new logs();
$vcdb = new vcdb();
$pcdb = new pcdb();

$tabbedoutput='';
$tabbedoutputrecords=array();
$streamXLSX=false;

if(isset($_POST['submit']) && strlen($_POST['input'])>0) 
{
 
 $input = $_POST['input'];
 $records = explode("\r\n", $input);
 $tabbedoutput="Partnumber\tPartType\tApplications\r\n";

 foreach ($records as $record) 
 {
  $fields = explode("\t", $record);
  if(count($fields)>=1)
  {   
   if($part=$pim->getPart(trim($fields[0])))
   {
    $summarytemp=$pim->getAppSummary($part['partnumber']);  
    if($summarytemp['age']>30 || $summarytemp['age']<0)
    {// existing summary is stale or missing - recapture it
           
     $apps=$pim->getAppsByPartnumber($fields[0]);
    
     $temp=array();
     foreach($apps as $app)
     {
      $mmy=$vcdb->getMMYforBasevehicleid($app['basevehicleid']);
      $key=$mmy['makename'].'_'.$mmy['modelname'];
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
     $pim->updateAppSummary($part['partnumber'], $summary);
    }
    else
    {// existing summary is usable 
     $summary=$summarytemp['summary'];   
    }
    
    $balance=$pim->getPartBalance($part['partnumber']);
    $qoh=0; $amd=0;
    if($balance){$qoh=$balance['qoh']; $amd=$balance['amd'];}
        
    $tabbedoutputrecord=$fields[0]."\t".$pim->partCategoryName($part['partcategory'])."\t".$part['GTIN']."\t".$pcdb->parttypeName($part['parttypeid'])."\t".$pcdb->lifeCycleCodeDescription($part['lifecyclestatus'])."\t".$part['replacedby']."\t".$qoh."\t".$amd."\t".$summary;
    $tabbedoutputrecords[]=$tabbedoutputrecord;
    $tabbedoutput.=$tabbedoutputrecord."\r\n";
   }
  }
 }
 
 if(isset($_POST['renderxlsx']))
 {
  $writer = new XLSXWriter();
  $writer->setAuthor('SandPIM');
  $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','UPC'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','Replaced By'=>'string','QoH'=>'integer','AMD'=>'integer','Applications'=>'string'), array('widths'=>array(18,20,13,30,20,18,10,10,150),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
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
                                <div><textarea name="input" rows="10" cols="80"><?php echo $tabbedoutput;?></textarea></div>
                                <div><input type="checkbox" id="renderxlsx" name="renderxlsx"/><label for="renderxlsx">Download As Excel Spreadsheet</label></div>


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
