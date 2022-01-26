<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportAPA65pricefileStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb();
$writer = new XLSXWriter();
$logs=new logs();
$pcdbVersion=$pcdb->version();

$receiverprofileid=intval($_GET['receiverprofile']);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);

        
$writer->writeSheetHeader('Data', 
        array(
'Price Sheet Effective Date'=>'string',
'Hazardous Material Flag Y/N'=>'string',
'Item Level GTIN'=>'string',
'Item Level GTIN Qualifier'=>'string',
'Part Number'=>'string',
'Item Quantity Size'=>'number',
'Item Quantity Size UOM'=>'string',
'Minimum Order Quantity'=>'number',
'Product Description - 20'=>'string',
'WD Price'=>'price',
'Part Number Superseded To'=>'string',
'Each Package Level GTIN'=>'string',
'Each Package Bar Code Characters'=>'string',
'Each Package Inner Quantity'=>'number',
'Each Package Inner Quantity UOM'=>'string',
'Currency'=>'string'
            ),
        array('widths'=>
            array(22,24,15,21,11,16,20,21,21,9,24,22,31,25,29,8),'freeze_rows'=>1, 
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#FFEB9C'],
            ['fill'=>'#FFEB9C'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'#C6EFCE'],
            ['fill'=>'']));
       
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  
     
  $row=array('2022-01-25','N','00'.$part['GTIN'],'UP',$partnumber,1,'EA',1,'Description (20)',12.34,$part['replacedby'],'00'.$part['GTIN'],$part['GTIN'],1,'EA','USD');
 } 
 $writer->writeSheetRow('Data', $row);
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('export', 0, 'APA/AWDA 6.5 exported of '.count($partnumbers).' parts; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{   
 $filename='APA-AWDA_pricefile_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>