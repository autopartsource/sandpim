<?php
include_once('./class/pimClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'partExpiReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'partExpiReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs=new logs();
$pcdb=new pcdb();
$user=new user();
$writer = new XLSXWriter();

$streamXLSX=false;
$xlsxdata='';

$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);

$matrix=array();

$expislist=array();
foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 $expis = $pim->getPartEXPIs($partnumber);
 foreach($expis as $expi)
 {
  if(!array_key_exists($expi['EXPIcode']."\t".$expi['languagecode'], $expislist))
  {
   $expislist[$expi['EXPIcode']."\t".$expi['languagecode']]=0;
  }  
  $matrix[$partnumber][$expi['EXPIcode']."\t".$expi['languagecode']]=$expi['EXPIvalue'];
 } 
}

$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string');
foreach($expislist as $columname=>$trash)
{
 $namebits= explode("\t",$columname);
 $EXPIcode=$namebits[0];
 $languagecode=$namebits[1];
 $description=$pcdb->EXPIcodeDescription($EXPIcode);
 $columnnames['['.$EXPIcode.'] '.$description.' - '.$languagecode]='string';
}
 
$columnwidths=array(12);
foreach($expislist as $columname=>$trsah){$columnwidths[]=intval(strlen($columname))*3;}
 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($expislist as $columname=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);

foreach($matrix as $partnumber=>$e)
{
 $part=$pim->getPart($partnumber);
 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 foreach($expislist as $columname=>$trash)
 {
  if(array_key_exists($columname, $e))
  {
   $row[]=$e[$columname];
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

$logs->logSystemEvent('export', 0, 'Generated EXPI coverage report containing '.count($partnumbers).' for delivery profile '.$receiverprofileid.'; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{  
 $filename='expi_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>