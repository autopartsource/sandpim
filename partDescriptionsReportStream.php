<?php
include_once('./class/pimClass.php');
include_once('./class/padbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/userClass.php');
include_once('./class/logsClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();


if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'exportPartDescriptionsStream.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
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

$descriptionslist=array();
foreach($partnumbers as $partnumber)
{
 $matrix[$partnumber]=array();
 $descriptions = $pim->getPartDescriptions($partnumber);  //$descriptions[]=array('id'=>$row['id'],'description'=>$row['description'],'descriptioncode'=>$row['descriptioncode'],'sequence'=>$row['sequence'],'languagecode'=>$row['languagecode']);
 foreach($descriptions as $description)
 {
  if(!array_key_exists($description['descriptioncode']."\t".$description['languagecode'], $descriptionslist))
  {
   $descriptionslist[$description['descriptioncode']."\t".$description['languagecode']]=0;
  }  
  $matrix[$partnumber][$description['descriptioncode']."\t".$description['languagecode']]=$description['description'];
 } 
}

$columnnames=array('Partnumber'=>'string','Lifecycle Status'=>'string');
foreach($descriptionslist as $columname=>$trash)
{
 $namebits= explode("\t",$columname);
 $descriptioncode=$namebits[0];
 $languagecode=$namebits[1];
 $descriptioncodename=$pcdb->partDescriptionTypeCodeDescription($descriptioncode);
 $columnnames['['.$descriptioncode.'] '.$descriptioncodename]='string';
}
 
$columnwidths=array(12);
foreach($descriptionslist as $columname=>$trsah){$columnwidths[]=intval(strlen($columname))*3;}
 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($descriptionslist as $columname=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);

foreach($matrix as $partnumber=>$descriptions)
{
 $part=$pim->getPart($partnumber);
 $row=array($partnumber, $pcdb->lifeCycleCodeDescription($part['lifecyclestatus']));
 foreach($descriptionslist as $columname=>$trash)
 {
  if(array_key_exists($columname, $descriptions))
  {
   $row[]=$descriptions[$columname];
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

$logs->logSystemEvent('export', 0, 'Generated description coverage report containing '.count($partnumbers).' for delivery profile '.$receiverprofileid.'; by:'.$_SERVER['REMOTE_ADDR']);

if($streamXLSX)
{  
 $filename='description_coverage_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>