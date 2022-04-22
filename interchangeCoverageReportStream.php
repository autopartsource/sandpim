<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'interchangeCoverageReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
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
$interchange=new interchange();
$pcdb=new pcdb();
$user=new user();
$writer = new XLSXWriter();

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

// build a matrix of partnumber/competitor
// columns are competitor brands, rows are partnumbers

$matrix=array();
$distinctbrands=array();

foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 $competitorparts=$interchange->getInterchangeByPartnumber($partnumber); //array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'competitorpartnumber'=>$row['competitorpartnumber'],'brandAAIAID'=>$row['brandAAIAID'],'interchangequantity'=>$row['interchangequantity'],'uom'=>$row['uom'],'interchangenotes'=>base64_decode($row['interchangenotes']),'internalnotes'=>base64_decode($row['internalnotes']));
 foreach($competitorparts as $competitorpart)
 {
  $distinctbrands[$competitorpart['brandAAIAID']]='';
  $matrix[$partnumber][$competitorpart['brandAAIAID']][]=$competitorpart['competitorpartnumber'];
 }
}


$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string');
foreach($distinctbrands as $distinctbrand=>$trash)
{
 $columnnames[$interchange->brandName($distinctbrand).' ('.$distinctbrand.')']='string';
}
 
$columnwidths=array(12);
foreach($distinctbrands as $distinctbrand=>$trsah){$columnwidths[]=20;} 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($distinctbrands as $distinctbrand=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);



foreach($matrix as $partnumber=>$columns)
{
 $part=$pim->getPart($partnumber);

 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 foreach($distinctbrands as $distinctbrand=>$trash)
 {
  if(array_key_exists($distinctbrand, $columns))
  {
    $row[]= implode(',',$columns[$distinctbrand]);
  }
  else
  {
   $row[]='';
  }
 }
 $writer->writeSheetRow('Sheet1', $row);
}


$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Interchange coverage report - '.count($partnumbers).' parts');

if($streamXLSX)
{   
 $filename='interchange_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>