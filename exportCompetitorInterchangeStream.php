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

$competitorBrandAAIAID=intval($_GET['competitorBrandAAIAID']);

$streamXLSX=false;
$xlsxdata='';

$writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Part Category'=>'string','Competitor Partnumber(s)'=>'string','Lifecycle Status'=>'string','Replaced By'=>'string'), array('widths'=>array(12,30,22,15,11),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
       
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

$logs->logSystemEvent('export', 0, 'Exported '.count($interchanges).' competitor interchange spreadsheet; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='interchange_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>