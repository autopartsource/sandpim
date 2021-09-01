<?php
include_once('./class/pimClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/logsClass.php');
include_once('./class/configGetClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ExperianVIOsnippetStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb=new vcdb();
$logs=new logs();
$configGet = new configGet();
$writer = new XLSXWriter();

$streamXLSX=false;
$xlsxdata='';

$basevehicleid=intval($_GET['basevehicleid']);

$mmy=$vcdb->getMMYforBasevehicleid($basevehicleid); //array('makename'=>$row['MakeName'],'modelname'=>$row['ModelName'],'year'=>$row['YearID'],'MakeID'=>$row['MakeID'],'ModelID'=>$row['ModelID']);
$makename='Not Found'; $modelname='Not Found'; $year=0;
if($mmy){$makename=$mmy['makename']; $modelname=$mmy['modelname']; $year=$mmy['year'];}

$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');
$viorecords=$pim->getExperianBasevehicleRecords($viogeography, $vioyearquarter, $basevehicleid);

$columnnames=array('Make'=>'string','Model'=>'string','Year'=>'number','SubModel'=>'string','Body Type'=>'string','Doors'=>'string','Drive Type'=>'string','Fuel'=>'string','Engine'=>'string','Engine VIN'=>'string','Fuel Delivery'=>'string','Trans Control'=>'string','Trans Speeds'=>'string','Aspiration'=>'string','Vehicle Type'=>'string','Population ('.$viogeography.' '.$vioyearquarter.')'=>'number');


$columnwidths=array(15,15,6,20,20,8,10,10,8,10,12,22,20,16,15,21);
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);


foreach($viorecords as $viorecord)
{
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
 
 $row=array($makename,$modelname,$year,$nicesubmodel, $nicebodytype, $nicedoors, $nicedrivetype, $nicefueltype, $niceenginebase, $niceenginevin, $niceFueldeliverysubtype, $nicetransmissioncontroltype, $nicetransmissionnumspeeds, $niceaspiration, $vehilcetypename, $vehilcecount);
 $writer->writeSheetRow('Sheet1', $row);
}


$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Experian VIO snippet - basevehilce: '.$basevehicleid);

if($streamXLSX)
{   
 $filename='Experian_VIO_basevehilce_'.$basevehicleid.'_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>