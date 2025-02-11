<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$navCategory = 'reports';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'applicationGuideReportStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
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
$user = new user();

$tabbedoutput='';
$tabbedoutputrecords=array();
$streamXLSX=false;
$tabbedoutput="Partnumber\tCategory\tPart Type\tLifecycle Status\tApplications\r\n";

 
$profile=$pim->getReceiverprofileById(intval($_GET['receiverprofile']));
if($profile)
{
 $profilename=$profile['name'];
 $profiledata=$profile['data'];//'ParentAAIAID:BQMC;BrandOwnerAAIAID:FLMK;CurrencyCode:USD;LanguageCode:EN;TechnicalContact:Luke Smith;ContactEmail:lsmith@autopartsource.com;';

 $profileelements=explode(';',$profiledata); $keyedprofile=array();
 foreach($profileelements as $profileelement)
 {
  $bits=explode(':',$profileelement);
  if(count($bits)==2){$keyedprofile[$bits[0]]=$bits[1];}
 }

 $partcategories=$pim->getReceiverprofilePartcategories($profile['id']);
 $partnumbers=$pim->getPartnumbersByPartcategories($partcategories);
 $user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $profile['id']);
}

 
if(count($partnumbers) > 250)
{// too big. Create a background job to generate the output
 $randomstring=$pim->newoid();
 $clientfilename='applicationGuide_'.$keyedprofile['DocumentTitle'].'_'.$randomstring.'_'.date('Y-m-d').'.xlsx';
 $localfilename=__DIR__.'/ACESexports/'.$randomstring;
 $token=$pim->createBackgroundjob('ApplicationGuideExport','started',$_SESSION['userid'],'',$localfilename,'receiverprofile:'.$profile['id'].';DocumentTitle:'.$keyedprofile['DocumentTitle'].';',date('Y-m-d H:i:s'),'text/xml',$clientfilename);
 $logs->logSystemEvent('Export', $_SESSION['userid'], 'Application Guide spreadsheet ['.$clientfilename.'] export setup for houskeeper; parts:'.count($partnumbers).' by:'.$_SERVER['REMOTE_ADDR']);
 echo 'This export will contain '.count($partnumbers).' parts. It will be processed by the houskeeper and be available in a few minutes at <a href="./downloadBackgroundExport.php?token='.$token.'">this link</a>';
 echo '<br/><br/><a href="./backgroundJobs.php">Go to background export jobs list</a>'; 
}
else
{
 foreach($partnumbers as $partnumber) 
 {
  if($part=$pim->getPart($partnumber))
  {
   $apps=$pim->getAppsByPartnumber($partnumber);
    
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
        
   $tabbedoutputrecord=$partnumber."\t".$pim->partCategoryName($part['partcategory'])."\t".$pcdb->parttypeName($part['parttypeid'])."\t".$pcdb->lifeCycleCodeDescription($part['lifecyclestatus'])."\t".$summary;
   $tabbedoutputrecords[]=$tabbedoutputrecord;
   $tabbedoutput.=$tabbedoutputrecord."\r\n";
  }
 }

 
 $writer = new XLSXWriter();
 $writer->setAuthor('SandPIM');
 $writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','Applications'=>'string'), array('widths'=>array(18,20,13,30,150),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
 foreach($tabbedoutputrecords as $tabbedoutputrecord)
 {
  $row=explode("\t",$tabbedoutputrecord);
  $writer->writeSheetRow('Sheet1', $row);
 }

 $xlsxdata=$writer->writeToString();
 
 $logs->logSystemEvent('reports', $_SESSION['userid'], 'Application Guide with '.count($partnumbers).' parts for receiver profile: '.$profilename);

 $filename='applicationguide_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;    
}
?>