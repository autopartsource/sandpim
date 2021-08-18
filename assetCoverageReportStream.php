<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'sandpiper index.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}


session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();
$pcdb = new pcdb();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);

        
$writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','Problem'=>'string'), array('widths'=>array(30,20,20),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
 

// build a matrix of partnumber/asset-type
// columns are asset types, rows are partnumbers
// every part get a P04 (primary photo) asset - regardless of if it exists or not. This way, there will always be at least 2 columns in the output (partnumber, Primary Photo)
// other asset types connected to any part in the population will result in that type's column in the output.
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);

 
 
 

 
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Asset coverage report - '.count($partnumbers).' parts');

if($streamXLSX)
{   
 $filename='asset_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>