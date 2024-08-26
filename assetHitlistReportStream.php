<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/assetClass.php');
include_once('./class/userClass.php');
include_once('./class/configGetClass.php');
include_once('./class/XLSXWriterClass.php');

$pim = new pim();

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'assetHitlistReportStream.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
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
$asset=new asset();
$user=new user();
$writer = new XLSXWriter();
$pcdbVersion=$pcdb->version();
$configGet = new configGet();

$targetassettype='P04';
$validassettypecode=false;
$allassettypes=$pcdb->getAssetTypeCodes();
foreach($allassettypes as $allassettype)
{
    if($_GET['assettypecode']==$allassettype['code']){$validassettypecode=true; break;}
}
if($validassettypecode){$targetassettype=$_GET['assettypecode'];}


$receiverprofileid=intval($_GET['receiverprofile']);
$user->setUserPreference($_SESSION['userid'], 'last receiverprofileid used', $receiverprofileid);
$viogeography=$configGet->getConfigValue('VIOdefaultGeography');
$vioyearquarter=$configGet->getConfigValue('VIOdefaultYearQuarter');


$streamXLSX=false;
$xlsxdata='';

$partcategories=$pim->getReceiverprofilePartcategories($receiverprofileid);
$lifecyclestatuses=$pim->getReceiverprofileLifecyclestatuses($receiverprofileid);
$partnumbers=$pim->getPartnumbersByPartcategories($partcategories,$lifecyclestatuses);


// build a matrix of partnumber/asset-type
// columns are asset types, rows are partnumbers
// every part get a P04 (primary photo) asset - regardless of if it exists or not. This way, there will always be at least 2 columns in the output (partnumber, Primary Photo)
// other asset types connected to any part in the population will result in that type's column in the output.

$matrix=array();
$assettypes=array();
$assettypes['P04']='';

foreach($partnumbers as $partnumber)
{
 if(!array_key_exists($partnumber, $matrix))
 {
  $matrix[$partnumber]=array();
 }
    
 $assetconnections=$asset->getAssetsConnectedToPart($partnumber); //array('id'=>$row['id'],'connectionid'=>$row['connectionid'],'assetid'=>$row['assetid'],'partnumber'=>$row['partnumber'],'assettypecode'=>$row['assettypecode'],'sequence'=>$row['sequence'],'representation'=>$row['representation'],'uri'=>$row['uri'],'filename'=>$row['filename']);

 foreach($assetconnections as $assetconnection)
 {
  $assettypes[$assetconnection['assettypecode']]='';
  $matrix[$partnumber][$assetconnection['assettypecode']][]=$assetconnection['assetid'];
 }

 if(!array_key_exists('P04', $matrix[$partnumber]))
 {
  $matrix[$partnumber]['P04']=[];
 } 
}







$columnnames=array('Partnumber'=>'string','Part Type'=>'string','Lifecycle Status'=>'string','Qty On-Hand'=>'number','Monthly Demand'=>'number','VIO'=>'number','Hybrid POP'=>'number');
foreach($assettypes as $assettype=>$trash)
{
 $columnnames[$pcdb->assetTypeCodeDescription($assettype)]='string';
}
 
$columnwidths=array(15,15,16,12,15,10,15);
foreach($assettypes as $assettype=>$trsah){$columnwidths[]=20;} 
$columnmeta=array('widths'=>$columnwidths,'freeze_rows'=>1 ,['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0'],['fill'=>'#c0c0c0']);
foreach($assettypes as $assettype=>$trsah){$columnmeta[]=['fill'=>'#c0c0c0'];}

$writer->writeSheetHeader('Sheet1', $columnnames, $columnmeta);

$viograndtotal=0;
$amdgrandtotal=0;

$rows=array();


foreach($matrix as $partnumber=>$columns)
{
 $part=$pim->getPart($partnumber); 
 $qoh=0; $amd=0;
 $partbalance=$pim->getPartBalance($partnumber);
 if($partbalance){$qoh=$partbalance['qoh']; $amd=$partbalance['amd'];}

 
  // exclude zero-onhand and non-avail lifecycle 
 if($part['lifecyclestatus']!='2' || ($partbalance && $qoh==0)){continue;}

 if(isset($matrix[$partnumber][$targetassettype]) && count($matrix[$partnumber][$targetassettype])){continue;}
  
 $viototal=$pim->partVIOtotal($partnumber, $viogeography, $vioyearquarter);
 $viograndtotal+=$viototal;
 $amdgrandtotal+=$amd;
 
 
 $row=array($partnumber, $pcdb->parttypeName($part['parttypeid']) ,$pcdb->lifeCycleCodeDescription($part['lifecyclestatus']),$qoh,$amd,$viototal,0);
 foreach($assettypes as $assettype=>$trash)
 {
  if(array_key_exists($assettype, $columns))
  {
    $row[]= implode(',',$columns[$assettype]);
  }
  else
  {
   $row[]='';
  }
 }
 
 $rows[]=$row;
}


$rowstemp=array();
$popkey=array();
$vioamdratio=(1+$viograndtotal)/(1+$amdgrandtotal); // compute the ration of amd to vio for scaling
foreach($rows as $row)
{// insert the hybrid pop value into the 6th column
 $row[6]= (($row[4]*$vioamdratio)+$row[5])/2;
 $rowstemp[]=$row;
}

foreach($rowstemp as $key=>$row)
{//compile an index using the 6th column
 $popkey[$key]=$row[6];
}

array_multisort($popkey,SORT_DESC,$rowstemp);


foreach($rowstemp as $row)
{
 $writer->writeSheetRow('Sheet1', $row);
}


//$writer->writeSheetRow('Sheet1', array('', '',0,$amdgrandtotal,$viograndtotal,$vioamdratio));
 





$writer->setAuthor('SandPIM'); 
$xlsxdata=$writer->writeToString();
$streamXLSX=true;

$logs->logSystemEvent('report', $_SESSION['userid'], 'Asset hitlist report - '.count($partnumbers).' parts');

if($streamXLSX)
{   
 $filename='asset_hitlist_'.date('Y-m-d').'.xlsx';
 header('Content-Disposition: attachment; filename="'.$filename.'"');
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
 header('Content-Length: ' . strlen($xlsxdata));
 header('Content-Transfer-Encoding: binary');
 header('Cache-Control: must-revalidate');
 header('Pragma: public');
 echo $xlsxdata;
}?>