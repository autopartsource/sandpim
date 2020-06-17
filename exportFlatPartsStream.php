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

$validPartTypes=array(); $partTypes=$pcdb->getPartTypes('%'); foreach($partTypes as $partType){$validPartTypes[$partType['id']]=$partType['name'];}

$receiverprofileid=intval($_GET['receiverprofile']);
$appcategories=$pim->getReceiverprofileAppcategories($receiverprofileid);
$partnumbers=$pim->getAppPartsByAppcategories($appcategories);

$streamXLSX=false;
$xlsxdata='';

$selectiontype=$_GET['selectiontype'];


$writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Part Type'=>'string'), array('widths'=>array(37,100),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  $parttypeid=$part['parttypeid'];
     
  $row=array($partnumber,$pcdb->parttypeName($parttypeid));
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

$logs->logSystemEvent('export', 0, 'Exported '.count($items).' items; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='items_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>