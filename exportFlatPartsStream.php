<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportFlatPartsStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb();
$user = new user();
$writer = new XLSXWriter();
$logs=new logs();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

        
$writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','GTIN'=>'string','Replaced By'=>'string','Part Category'=>'string','Created On'=>'string','First Stocked On'=>'string','Discontinued Date'=>'string'), array('widths'=>array(12,30,22,15,11,25,10,15,16),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
       
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
     
  $row=array($partnumber,$pcdb->parttypeName($part['parttypeid']),$pcdb->lifeCycleCodeDescription($part['lifecyclestatus']), $part['GTIN'],$part['replacedby'],$pim->partCategoryName($part['partcategory']),$part['createdDate'],$part['firststockedDate'],$part['discontinuedDate']);
 }
 else
 { // part is not in the part master list
  $row=array($partnumber,'');
 }
 
 $writer->writeSheetRow('Sheet1', $row);
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('export', 0, 'Exported '.count($partnumbers).' parts; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='parts_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>