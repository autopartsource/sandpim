<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'parttypeHolesReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
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
$pcdb=new pcdb();
$user=new user();
$configGet = new configGet();
$writer = new XLSXWriter();

$parttypeid=intval($_GET['parttypeid']);
$positionid=intval($_GET['positionid']);
$positionname='Any'; if($positionid>0){$positionname=$pcdb->positionName($positionid);}

$parttypename=$pcdb->parttypeName($parttypeid);
$countthreshold=10000; if(isset($_GET['countthreshold'])){$countthreshold=intval($_GET['countthreshold']);}
$fromyear=intval($_GET['fromyear']);

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$viorecords=$pim->getExperianRecords($viogeography, $vioyearquarter,0);  
$favoritepositions=$pim->getFavoritePositions();



$notes='Part Type: '.$parttypename.'; Model-years from: '.$fromyear.'; VIO Threshold:'. $countthreshold.'; Position: '.$positionname;

$streamXLSX=false;
$xlsxdata='';
$columnnames=array('Make'=>'string','Model'=>'string','Year'=>'number','Population ('.$viogeography.' '.$vioyearquarter.')'=>'number',$notes=>'string');
$columnwidths=array(15,15,6,20);
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta); 





$keyedvio=array();
$viototalbybasevid=array();
foreach($viorecords as $viorecord)
{
 $keyedvio[$viorecord['basevehicleid']][]=$viorecord; 
 if(array_key_exists($viorecord['basevehicleid'], $viototalbybasevid))
 {
     $viototalbybasevid[$viorecord['basevehicleid']]+=$viorecord['vehiclecount'];
 }
 else
 {
     $viototalbybasevid[$viorecord['basevehicleid']]=$viorecord['vehiclecount'];     
 }
 
}

$apps=$pim->getAppsByParttype($parttypeid,false); // the false arg is telling the backend to not include app attribures for the sake for speed


// grind apps into a basevid-keyed array for lookup
$keyedapps=array();
foreach($apps as $app)
{
    if($positionid==0 || $app['positionid']==$positionid)
    {
     $keyedapps[$app['basevehicleid']][]=$app;
    }
}




//get all VCdb basevehicles with model-years since threshold year 
$availablebasevids=$vcdb->getAllBaseVehicles(); //$basevehicles[$row['BaseVehicleID']] = array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'vehicletypeid'=>$row['VehicleTypeID']);
foreach($availablebasevids as $id=>$availablebasevehicle)
{
    //&& $availablebasevehicle['vehicletypeid']==5
 if($availablebasevehicle['year']>=$fromyear  && !array_key_exists($id, $keyedapps))
 {
  // this basevehicle is not covered by the apps with the given part-type
  if(array_key_exists($id, $keyedvio) && $viototalbybasevid[$id]>$countthreshold)  
  { // basevehicleid exists in VIO - we case about it
   $row=array($availablebasevehicle['makename'],$availablebasevehicle['modelname'],$availablebasevehicle['year'],$viototalbybasevid[$id]);
   $writer->writeSheetRow('Sheet1', $row);   
  }
 }
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'VIO coverage report');

if($streamXLSX)
{   
 $filename='parttype_holes_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>