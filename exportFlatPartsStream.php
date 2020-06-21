<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');
$navCategory = 'import/export';

session_start();

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);

        
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