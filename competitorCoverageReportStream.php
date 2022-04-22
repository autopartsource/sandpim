<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'competitorCoverageReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
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
$user=new user();
$interchange=new interchange();
$writer = new XLSXWriter();

$receiverprofileid=intval($_GET['receiverprofile']);
$brandid=$_GET['competitivebrand'];

$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$user->setUserPreference($_SESSION['userid'], 'last brandid used', $brandid);

if(!$interchange->validBrand($brandid))
{
 $logs->logSystemEvent('accesscontrol',0, 'competitorCoverageReportStream.php - invalid brand ('.$brandid.') supplied by '.$_SERVER['REMOTE_ADDR']);
 exit;    
}

$competitorbrandname=$interchange->brandName($brandid);


$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);


// buiild a partnumber-keyed lookup of all our parts in the given receiver profile for fast lookup later
$ourpartsdict=array(); foreach($partnumbers as $partnumber){$ourpartsdict[$partnumber]='';} ksort($ourpartsdict);


// build a list of competitor partnumbers of the given specific brand.
// this (distinct) list of competitor's parts will fill the first column


$allinterchanges=$interchange->getInterchangesByCompetitorBrand($brandid);
//array('partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'interchangenotes'=>base64_decode($row['interchangenotes']),'internalnotes'=>base64_decode($row['internalnotes']));

$competitorpartsdict=array();
foreach($allinterchanges as $allinterchange)
{
 $competitorpartsdict[$allinterchange['competitorpartnumber']]=array();
}
ksort($competitorpartsdict);


foreach($allinterchanges as $allinterchange)
{
 if($_GET['receiverprofile']=='all' || array_key_exists($allinterchange['partnumber'], $ourpartsdict))
 { // we only care about our parts that are part of this receiver profiles's list
  $competitorpartsdict[$allinterchange['competitorpartnumber']][]=$allinterchange['partnumber'];
 }
}

$columnnames=array('Competitor'=>'string','Our Parts'=>'string');
$columnmeta=array('widths'=>array(20,50),'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);



foreach($competitorpartsdict as $competitorpart=>$ourparts)
{
 if(count($ourparts)==0){continue;}
 $ourpartssorted=array(); foreach($ourparts as $ourpart){$ourpartssorted[$ourpart]='';} ksort($ourpartssorted);
 $row=array($competitorpart, implode(',', array_keys($ourpartssorted)));
 $writer->writeSheetRow('Sheet1', $row);
}


$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'competitor coverage report - '.$competitorbrandname);

if($streamXLSX)
{   
 $filename='competitor_'.$brandid.'_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>