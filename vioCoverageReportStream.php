<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'vioCoverageReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();
$vcdb=new vcdb();
$user=new user();
$configGet = new configGet();
$writer = new XLSXWriter();

$countthreshold=10000; if(isset($_GET['countthreshold'])){$countthreshold=intval($_GET['countthreshold']);}
$hidecovered=false; if(isset($_GET['hidecovered'])){$hidecovered=true;}

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$viorecords=$pim->getExperianRecords($viogeography, $vioyearquarter,$countthreshold);


$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$apps=$pim->getAppsByPartcategories($partcategories);




// grind apps into a basevid-keyed array for past lookup
$keyedapps=array();
foreach($apps as $app)
{
    $keyedapps[$app['basevehicleid']][]=$app;
}


$streamXLSX=false;
$xlsxdata='';




$columnnames=array('Make'=>'string','Model'=>'string','Year'=>'number','SubModel'=>'string','Body Type'=>'string','Doors'=>'string','Drive Type'=>'string','Fuel'=>'string','Engine'=>'string','Engine VIN'=>'string','Fuel Delivery'=>'string','Trans Control'=>'string','Trans Speeds'=>'string','Aspiration'=>'string','Vehicle Type'=>'string','Population ('.$viogeography.' '.$vioyearquarter.')'=>'number','Covering Parts'=>'string');


$columnwidths=array(15,15,6,20,20,8,10,10,8,10,12,22,20,16,15,21,20);
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);


$recordcount=0;

foreach($viorecords as $viorecord)
{   
 // lookup vehicle in apps list to see if it's covered.
 
 $recordcount++;
 $partnumbers=array();
 $vehicleiscovered=false;
 
 if(array_key_exists($viorecord['basevehicleid'], $keyedapps))
 {
  // we have at lease one app that has this basevehcile. Now compare experian-usable vcdb attributes to see it still covers the vio vehicle
  $vehicleiscovered=true;

  foreach($keyedapps[$viorecord['basevehicleid']] as $app)
  {
   // see if this app has any experian-useful vcdb attributes
   if($pim->attributesAreExperianUseful($app['attributes']))
   {// app contains VCdb attributes that may connect to this experian vehicle
    
       
    $usefulattributes=array();
    foreach($app['attributes'] as $appattribute)
    {
     if($appattribute['type']=='vcdb' && ($appattribute['name']=='SubModel' || $appattribute['name']=='BodyType' || $appattribute['name']=='BodyNumDoors' || $appattribute['name']=='DriveType' || $appattribute['name']=='FuelType' || $appattribute['name']=='EngineBase' || $appattribute['name']=='EngineVIN' || $appattribute['name']=='FuelDeliverySubType' || $appattribute['name']=='TransmissionControlType' || $appattribute['name']=='TransmissionNumSpeeds' || $appattribute['name']=='Aspiration'))
     {
      $usefulattributes[]=$appattribute;    
     }    
    }
    
    // we no have all the experian-useful fitment attributes from this app in an array 
    // see if all of their values equal the current vio record's values
    
    $vehicleiscovered=true;
    
    foreach($usefulattributes as $usefulattribute)
    {
      switch($usefulattribute['name'])
      {
       case 'SubModel': if($usefulattribute['value']!=$viorecord['submodelid']){$vehicleiscovered=false;} break;
       case 'BodyType':  if($usefulattribute['value']!=$viorecord['bodytypeid']){$vehicleiscovered=false;} break;
       case 'BodyNumDoors':  if($usefulattribute['value']!=$viorecord['bodynumdoorsid']){$vehicleiscovered=false;} break;
       case 'DriveType': if($usefulattribute['value']!=$viorecord['drivetypeid']){$vehicleiscovered=false;} break;
       case 'FuelType':  if($usefulattribute['value']!=$viorecord['fueltypeid']){$vehicleiscovered=false;} break;
       case 'EngineBase':  if($usefulattribute['value']!=$viorecord['enginebaseid']){$vehicleiscovered=false;} break;
       case 'EngineVIN':  if($usefulattribute['value']!=$viorecord['enginevinid']){$vehicleiscovered=false;} break;
       case 'FuelDeliverySubType':  if($usefulattribute['value']!=$viorecord['fueldeliverysubtypeid']){$vehicleiscovered=false;} break;
       case 'TransmissionControlType':  if($usefulattribute['value']!=$viorecord['transmissioncontroltypeid']){$vehicleiscovered=false;} break;
       case 'TransmissionNumSpeeds':  if($usefulattribute['value']!=$viorecord['transmissionnumspeedsid']){$vehicleiscovered=false;} break;
       case 'Aspiration':  if($usefulattribute['value']!=$viorecord['aspirationid']){$vehicleiscovered=false;} break;
       default: break;
      }
    }
    
    if($vehicleiscovered)
    {
     if(!in_array($app['partnumber'], $partnumbers)){$partnumbers[]=$app['partnumber'];}   
    }
   }
   else
   {// this app fitment attributes are not mappable to experian data. We must conculde that our the app fits this particular experian vehilce records
       // indicate the coverage by adding the app's part to the list of parts fitting this vehicle       
    if(!in_array($app['partnumber'], $partnumbers)){$partnumbers[]=$app['partnumber'];}
   }
  }
 }

 $partnumberlist=implode(',', $partnumbers);


 //if($recordcount>500){break;}
 
 if(count($partnumbers)>0 && $hidecovered){continue;}
 
 
 $mmy=$vcdb->getMMYforBasevehicleid($viorecord['basevehicleid']); //array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'MakeID'=>$row['MakeID'],'ModelID'=>$row['ModelID']);
 $makename='Not Found'; $modelname='Not Found'; $year=0;
 if($mmy){$makename=$mmy['makename']; $modelname=$mmy['modelname']; $year=$mmy['year'];}
 
 $nicesubmodel=$vcdb->niceVCdbAttributePair(['name'=>'SubModel', 'value'=>$viorecord['submodelid']]); 
 $nicebodytype=$vcdb->niceVCdbAttributePair(['name'=>'BodyType', 'value'=>$viorecord['bodytypeid']]); 
 $nicedoors=$vcdb->niceVCdbAttributePair(['name'=>'BodyNumDoors', 'value'=>$viorecord['bodynumdoorsid']]); 
 $nicedrivetype=$vcdb->niceVCdbAttributePair(['name'=>'DriveType', 'value'=>$viorecord['drivetypeid']]); 
 $nicefueltype=$vcdb->niceVCdbAttributePair(['name'=>'FuelType', 'value'=>$viorecord['fueltypeid']]); 
 $niceenginebase=$vcdb->niceVCdbAttributePair(['name'=>'EngineBase', 'value'=>$viorecord['enginebaseid']]);
 $niceenginevin=$vcdb->niceVCdbAttributePair(['name'=>'EngineVIN', 'value'=>$viorecord['enginevinid']]);
 $niceFueldeliverysubtype=$vcdb->niceVCdbAttributePair(['name'=>'FuelDeliverySubType', 'value'=>$viorecord['fueldeliverysubtypeid']]);
 $nicetransmissioncontroltype=$vcdb->niceVCdbAttributePair(['name'=>'TransmissionControlType', 'value'=>$viorecord['transmissioncontroltypeid']]);
 $nicetransmissionnumspeeds=$vcdb->niceVCdbAttributePair(['name'=>'TransmissionNumSpeeds', 'value'=>$viorecord['transmissionnumspeedsid']]);
 $niceaspiration=$vcdb->niceVCdbAttributePair(['name'=>'Aspiration', 'value'=>$viorecord['aspirationid']]);
 $vehilcetypename=$vcdb->vehicleTypeName($viorecord['vehicletypeid']);
 $vehilcecount=$viorecord['vehiclecount'];
 
 $row=array($makename,$modelname,$year,$nicesubmodel, $nicebodytype, $nicedoors, $nicedrivetype, $nicefueltype, $niceenginebase, $niceenginevin, $niceFueldeliverysubtype, $nicetransmissioncontroltype, $nicetransmissionnumspeeds, $niceaspiration, $vehilcetypename, $vehilcecount,$partnumberlist);
 $writer->writeSheetRow('Sheet1', $row);
}


$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'VIO coverage report');

if($streamXLSX)
{   
 $filename='VIO_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>