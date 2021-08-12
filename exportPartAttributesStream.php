<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');

session_start();

$pim = new pim();
$logs=new logs();

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportPartAttributesStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$padb=new padb();
$writer = new XLSXWriter();


$streamXLSX=false;
$xlsxdata='';

$receiverprofileid=intval($_GET['receiverprofile']);
$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories);   
$matrix=array();

$attributeslist=array();
foreach($partnumbers as $partnumber)
{
 $part=$pim->getPart($partnumber);
 if($part)
 {  
  $attributes = $pim->getPartAttributes($partnumber); //    $attributes[]=array('id'=>$row['id'],'PAID'=>$row['PAID'],'name'=>$row['userDefinedAttributeName'],'value'=>$row['value'],'uom'=>$row['uom']);
  foreach($attributes as $attribute)
  {
   if(!array_key_exists($attribute['PAID']."\t".$attribute['name'], $attributeslist))
   {
    $attributeslist[$attribute['PAID']."\t".$attribute['name']]=0;
   }
   
   $matrix[$partnumber][$attribute['PAID']."\t".$attribute['name']]=$attribute['value'];
  } 
 }
}

$columnnames=array('Partnumber'=>'string');
foreach($attributeslist as $columname=>$trash)
{
 $namebits= explode("\t",$columname);
 $PAID=$namebits[0];
 $padbname=$padb->PAIDname($PAID);
 if($PAID==0)
 {
  $columnnames['[non-PAdb] '.$namebits[1]]='string';
 }
 else
 {
  $columnnames['['.$PAID.'] '.$padbname]='string';
 }
}
 
$columnwidths=array(12);
foreach($attributeslist as $columname=>$trsah){$columnwidths[]=intval(strlen($columname));}
 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0']);
foreach($attributeslist as $columname=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);




foreach($matrix as $partnumber=>$attributes)
{
 $row=array($partnumber);
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

$logs->logSystemEvent('export', 0, 'Exported attributes for '.count($partnumbers).' parts; by:'.$_SERVER['REMOTE_ADDR']);

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