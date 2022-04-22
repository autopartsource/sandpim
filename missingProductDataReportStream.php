<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/packagingClass.php');
include_once('./class/userClass.php');
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
$user=new user();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();
$packaging=new packaging();

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);

$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);
        
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
   $issuehash=md5('PART/GTIN/MISSING'.$partnumber.'missing GTIN'.'manual report run');
   if(!$pim->getIssueByHash($issuehash))
   {
    $pim->recordIssue('PART/GTIN/MISSING',$partnumber,1,'missing GTIN','manual report run', $issuehash);
   }
  }
     
  $packages=$packaging->getPackagesByPartnumber($partnumber);
  if(count($packages)==0)
  {// no package records for this item
   $row=array($partnumber,'package','no packages');
   $writer->writeSheetRow('Sheet1', $row);
   $issuehash=md5('PART/PACKAGE/MISSING'.$partnumber.'missing package'.'manual report run');
   if(!$pim->getIssueByHash($issuehash))
   {
    $pim->recordIssue('PART/PACKAGE/MISSING',$partnumber,1,'missing package','manual report run', $issuehash);
   }
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
    
    $issuehash=md5('PART/PACKAGE/WEIGHT'.$partnumber.'0 weight package'.'manual report run');
    if(!$pim->getIssueByHash($issuehash))
    {
     $pim->recordIssue('PART/PACKAGE/WEIGHT',$partnumber,1,'0 weight package','manual report run', $issuehash);
    }
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
    $issuehash=md5('PART/PACKAGE/DIMS'.$partnumber.'0 shipping dims package'.'manual report run');
    if(!$pim->getIssueByHash($issuehash))
    {
     $pim->recordIssue('PART/PACKAGE/DIMS',$partnumber,1,'0 shipping dims package','manual report run', $issuehash);
    }
   }
  }

  
  
  
  
 }
 
}

$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Missing product data - '.count($partnumbers).' parts');

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