<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/XLSXWriterClass.php');

session_start();

$pim = new pim();
$logs=new logs();
$pcdb = new pcdb();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();
$packaging=new packaging();

$receiverprofileid=intval($_GET['receiverprofile']);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);

        
$writer->writeSheetHeader('Sheet1', array('Partnumber'=>'string','Category'=>'string','Problem'=>'string'), array('widths'=>array(30,20,20),'freeze_rows'=>1, ['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']));
       
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {
  
  if($part['GTIN']=='')
  {
   $row=array($partnumber,'core','missing GTIN');
   $writer->writeSheetRow('Sheet1', $row);
  }
     
  $packages=$packaging->getPackagesByPartnumber($partnumber);
  if(count($packages)==0)
  {// no package records for this item
   $row=array($partnumber,'package','no packages');
   $writer->writeSheetRow('Sheet1', $row);
  }
  else
  {// package records exist - look for zero values in weight and dims
   $foundproblem=false;
   foreach($packages as $package)
   {
    if($package['weight']==0){$foundproblem=true;}
   }
   if($foundproblem)
   {
    $row=array($partnumber,'package','0 weight package');
    $writer->writeSheetRow('Sheet1', $row);      
   }
   
   $foundproblem=false;
   foreach($packages as $package)
   {
    if($package['shippingheight']==0 || $package['shippinglength']==0 || $package['shippingwidth']==0){$foundproblem=true;}
   }
   if($foundproblem)
   {
    $row=array($partnumber,'package','0 shipping dims package');
    $writer->writeSheetRow('Sheet1', $row);      
   }
  }

  
  
  
  
 }
 
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', 0, 'Missing product data - '.count($partnumbers).' parts');

if($streamXLSX)
{   
 $filename='missing_product_data_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>