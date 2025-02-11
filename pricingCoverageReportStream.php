<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pricingClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'pricingCoverageReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 
 exit;
}

session_start();
if (!isset($_SESSION['userid']))
{
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$logs=new logs();
$pricing=new pricing();
$pcdb=new pcdb();
$user=new user();
$writer = new XLSXWriter();
$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

// build a matrix of partnumber/pricetype
// columns are types like "JBR (USD)", rows are partnumbers

$matrix=array();
$dictinctpricetypes=array();

foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 //$partprices=$pricing->getCurrentPricesByPartnumber($partnumber);  // array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate'],'niceprice'=>$niceprice);
 $partprices=$pricing->getPricesByPartnumber($partnumber);  // array('id'=>$row['id'],'partnumber'=>$row['partnumber'],'pricesheetnumber'=>$row['pricesheetnumber'],'amount'=>$row['amount'],'currency'=>$row['currency'],'priceuom'=>$row['priceuom'],'pricetype'=>$row['pricetype'],'effectivedate'=>$row['effectivedate'],'expirationdate'=>$row['expirationdate'],'niceprice'=>$niceprice);
 foreach($partprices as $partprice)
 {
  $dictinctpricetypes[$partprice['pricetype'].' ('.$partprice['currency'].')']='';
  $matrix[$partnumber][$partprice['pricetype'].' ('.$partprice['currency'].')'][]=$partprice['amount'];
 }
}


$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string');
foreach($dictinctpricetypes as $dictinctpricetype=>$trash)
{
 $columnnames[$dictinctpricetype]='string';
}
 
$columnwidths=array(12);
foreach($dictinctpricetypes as $dictinctpricetype=>$trsah){$columnwidths[]=20;} 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($dictinctpricetypes as $dictinctpricetype=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);


foreach($matrix as $partnumber=>$columns)
{
 $part=$pim->getPart($partnumber);
 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 
 foreach($dictinctpricetypes as $dictinctpricetype=>$trash)
 {
  if(array_key_exists($dictinctpricetype, $columns))
  {
    $row[]= implode(',',$columns[$dictinctpricetype]);
  }
  else
  {
   $row[]='';
  }
 }
 $writer->writeSheetRow('Sheet1', $row);
}


$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Pricing coverage report - '.count($partnumbers).' parts');

if($streamXLSX)
{   
 $filename='pricing_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>