<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'partAttributeCoverageReportStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportPartAttributesStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$logs=new logs();
$padb=new padb();
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

$attributeslist=array();
foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 $attributes = $pim->getPartAttributes($partnumber); //    $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'name'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
 foreach($attributes as $attribute)
 {
  if(!array_key_exists($attribute['PAID']."\t".$attribute['name']."\t".$attribute['uom'], $attributeslist))
  {
   $attributeslist[$attribute['PAID']."\t".$attribute['name']."\t".$attribute['uom']]=0;
  }  
  $matrix[$partnumber][$attribute['PAID']."\t".$attribute['name']."\t".$attribute['uom']]=$attribute['value'];
 } 
}

$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string');
foreach($attributeslist as $columname=>$trash)
{
 $namebits= explode("\t",$columname);
 $PAID=$namebits[0];
 $uom=$namebits[2]; if($uom!=''){$uom=' ('.$uom.')';}
 $padbname=$padb->PAIDname($PAID);
 if($PAID==0)
 {
  $columnnames['[non-PAdb] '.$namebits[1].$uom]='string';
 }
 else
 {
  $columnnames['['.$PAID.'] '.$padbname.$uom]='string';
 }
}
 
$columnwidths=array(12);
foreach($attributeslist as $columname=>$trsah){$columnwidths[]=intval(strlen($columname))*3;}
 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($attributeslist as $columname=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);

foreach($matrix as $partnumber=>$attributes)
{
 $part=$pim->getPart($partnumber);
 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 foreach($attributeslist as $columname=>$trash)
 {
  if(array_key_exists($columname, $attributes))
  {
   $row[]=$attributes[$columname];
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

$logs->logSystemEvent('export', 0, 'Generated attribute coverage report containing '.count($partnumbers).' for delivery profile '.$receiverprofileid.'; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{  
 $filename='attribute_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>