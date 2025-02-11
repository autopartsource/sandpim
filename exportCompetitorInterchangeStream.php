<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');
$navCategory = 'export';


$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'exportCompetitorInterchangeStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if(!isset($_SESSION['userid'])){echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>"; exit;}

$interchange=new interchange;
$logs=new logs();
$user= new user();
$pcdb = new pcdb();
$writer = new XLSXWriter();
//$pcdbVersion=$pcdb->version();

$competitorBrandAAIAID=$_GET['competitorBrandAAIAID'];
$user->setUserPreference($_SESSION['userid'], 'last brandid used', $competitorBrandAAIAID);
$competitorBrandName=$interchange->brandName($competitorBrandAAIAID);
$interchanges=$interchange->getInterchangesByCompetitorBrand($competitorBrandAAIAID);

$streamXLSX=false;
$xlsxdata='';

$writer->writeSheetHeader('Raw Data', array(
    'Partnumber'=>'string',
    'Part Type'=>'string',
    'Part Category'=>'string',
    $competitorBrandName.' Part'=>'string',
    'Lifecycle Status'=>'string',
    'Item-Level GTIN'=>'string',
    'Replaced By'=>'string','Internal Notes'=>'string','Public Comments'=>'string'), array('widths'=>array(12,30,30,30,22,15,11,40,40),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
       
foreach($interchanges as $interchange)
{
 $part=$pim->getPart($interchange['partnumber']);
 if($part)
 {
  $row=array($interchange['partnumber'],
      $pcdb->parttypeName($part['parttypeid']),
      $pim->partCategoryName($part['partcategory']),
      $interchange['competitorpartnumber'],
      $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']), 
      $part['GTIN'],
      $part['replacedby'],$interchange['internalnotes'],$interchange['interchangenotes']);
 }
 $writer->writeSheetRow('Raw Data', $row);
}

//-------------- create the second sheet (Consolidated) ----------------------
$consolidated=array();

foreach($interchanges as $interchange)
{
 $consolidated[$interchange['partnumber']][]=$interchange['competitorpartnumber'];   
}

$writer->writeSheetHeader('Consolidated', array('Partnumber'=>'string', $competitorBrandName.' Part(s)'=>'string'), array('widths'=>array(12,30),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
       
foreach($consolidated as $partnumber=>$competitorparts)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  $row=array($partnumber, implode(',',$competitorparts));
 }
 $writer->writeSheetRow('Consolidated', $row);
}

//--------------------------------



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