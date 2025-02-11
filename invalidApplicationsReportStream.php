<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/vcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/XLSXWriterClass.php');

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb();
$vcdb=new vcdb();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$applications=$pim->getAppsByPartcategories($partcategories);

        
$writer->writeSheetHeader('Sheet1', array('AppID'=>'integer','Partnumber'=>'string','Make'=>'string','Model'=>'string','Year'=>'integer','Part Type'=>'string','Position'=>'string','Category'=>'string','Problem'=>'string'), array('widths'=>array(10,20,30,30,5,10,10,20,20),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
 
$positioncache=array();
$parttypenamecache=array();
$partnumbercache=array();
$validparttypepositioncache=array();
$validAttributesCache=array();



foreach($applications as $app)
{
 $applicationid=$app['id'];
 $partnumber=$app['partnumber'];
 $basevehicleid=$app['basevehicleid'];
 $positionid=$app['positionid'];
 $parttypeid=$app['parttypeid'];
 if(!array_key_exists($partnumber, $partnumbercache))
 {// first time seeing this partnumber
  $partnumbercache[$partnumber]=$pim->getPart($partnumber);
 }
 
 if(!array_key_exists($parttypeid.'-'.$positionid, $validparttypepositioncache))
 {
  $validparttypepositioncache[$parttypeid.'-'.$positionid]=$pcdb->validParttypePosition($parttypeid, $positionid);
 }

 if(!array_key_exists($positionid, $positioncache))
 {
  $positioncache[$positionid]=$pcdb->positionName($positionid);
 }
 $positionname=$positioncache[$positionid];
 
 
 if(!array_key_exists($parttypeid, $parttypenamecache))
 {
  $parttypenamecache[$parttypeid]=$pcdb->parttypeName($parttypeid);
 }
 $parttypename=$parttypenamecache[$parttypeid];
  
 
 if(!array_key_exists($basevehicleid,$validAttributesCache))
 {
  $validAttributesCache[$basevehicleid]=$vcdb->getACESattributesForBasevehicle($basevehicleid); 
 }
 $validAttributes=$validAttributesCache[$basevehicleid];
 
 
 
 
 
 
 if($partnumbercache[$partnumber]==false)
 {
  // partnumber in this app is not valid
  $mmy=$vcdb->getMMYforBasevehicleid($basevehicleid); // look up the make/model/year of the app
  $row=array($applicationid,$partnumber,$mmy['makename'],$mmy['modelname'],$mmy['year'],$parttypename,$positionname,'core','invalid partnumber');
  $writer->writeSheetRow('Sheet1', $row);
  $issuehash=md5('APP/PART/INVALID'.$partnumber.'invalid part'.'manual report run');
  if(!$pim->getIssueByHash($issuehash))
  {
   $pim->recordIssue('APP/PART/INVALID',$partnumber,1,'invalid part','manual report run', $issuehash);
  }
 }
 
 
 if($partnumbercache[$partnumber] && $partnumbercache[$partnumber]['parttypeid']!=$parttypeid)
 {
  // parttype in the app does not match patt type in the part master record
  $mmy=$vcdb->getMMYforBasevehicleid($basevehicleid); // look up the make/model/year of the app
  $row=array($applicationid,$partnumber,$mmy['makename'],$mmy['modelname'],$mmy['year'],$parttypename,$positionname,'core','app parttype ('.$parttypeid.') <> part master parttype('.$partnumbercache[$partnumber]['parttypeid'].')');
  $writer->writeSheetRow('Sheet1', $row);
  $issuehash=md5('APP/PARTTYPE/MISMATCH'.$partnumber.'app parttype<> part master parttype'.'manual report run');
  if(!$pim->getIssueByHash($issuehash))
  {
   $pim->recordIssue('APP/PARTTYPE/MISMATCH',$partnumber,1,'app parttype<> part master parttype','manual report run', $issuehash);
  }
 }
 
 
 if(!$validparttypepositioncache[$parttypeid.'-'.$positionid])
 {
  // parttype-position combo is not valid
  $mmy=$vcdb->getMMYforBasevehicleid($basevehicleid); // look up the make/model/year of the app
  $row=array($applicationid,$partnumber,$mmy['makename'],$mmy['modelname'],$mmy['year'],$parttypename,$positionname,'core','position ('.$positionname.') is not valid for parttype ('.$parttypename.')');
  $writer->writeSheetRow('Sheet1', $row);
  $issuehash=md5('APP/POSITION/INVALID'.$partnumber.'position ('.$positionname.') is not valid for parttype ('.$parttypename.')'.'manual report run');
  if(!$pim->getIssueByHash($issuehash))
  {
   $pim->recordIssue('APP/POSITION/INVALID',$partnumber,1,'position ('.$positionname.') is not valid for parttype ('.$parttypename.')','manual report run', $issuehash);
  }
 }
 
 
 
 
 
 
 
 
 
}
/*
$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Invalid applications - '.count($applications).' applications');

if($streamXLSX)
{   
 $filename='invalid_applications_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
  }
 */
?>