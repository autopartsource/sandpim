<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();


if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partPackagesReportStream.php - access denied (404 sent) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}


$logs=new logs();
$pcdb=new pcdb();
$user=new user();
$packaging=new packaging();
$writer = new XLSXWriter();

$streamXLSX=false;
$xlsxdata='';

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

$matrix=array();

foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 $packages=$packaging->getPackagesByPartnumber($partnumber); //  []=array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'packageuom'=>$row['packageuom'],'quantityofeaches'=>$row['quantityofeaches'],'innerquantity'=>$innerquantity,'innerquantityuom'=>$row['innerquantityuom'],'weight'=>$weight,'weightsuom'=>$row['weightsuom'],'packagelevelGTIN'=>$row['packagelevelGTIN'],'packagebarcodecharacters'=>$row['packagebarcodecharacters'],'shippingheight'=>$shippingheight,'shippingwidth'=>$shippingwidth,'shippinglength'=>$shippinglength,'dimensionsuom'=>$row['dimensionsuom'],'nicepackage'=>$nicepackage);

 foreach($packages as $package)
 {
  $matrix[$partnumber][]=$package['nicepackage'];
 } 
}

$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string','Package'=>'string');
 
$columnwidths=array(12,15,30); 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);

foreach($matrix as $partnumber=>$packages)
{
 $part=$pim->getPart($partnumber);
 $packagestring='';
 if(count($packages)){$packagestring= implode('; ', $packages);}
 
 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']),$packagestring);
 $writer->writeSheetRow('Sheet1', $row);
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('export', 0, 'Generated package coverage report containing '.count($partnumbers).' for delivery profile '.$receiverprofileid.'; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{  
 $filename='package_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>