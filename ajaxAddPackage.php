<?php
include_once('./class/pimClass.php');
include_once('./class/packagingClass.php');
include_once('./class/logsClass.php');

$pim= new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddPackage.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();

$packaging= new packaging;

$result=array('success'=>false,'id'=>0,'oid'=>'','nicepackage'=>'');

if(isset($_SESSION['userid']) && isset($_GET['partnumber']) && (isset($_GET['packageuom']) || isset($_GET['quantityofeaches']) || isset($_GET['innerquantity']) || isset($_GET['innerquantityuom']) || isset($_GET['weight']) || isset($_GET['weightsuom']) || isset($_GET['packagelevelgtin']) || isset($_GET['packagebarcodecharacters']) || isset($_GET['shippingheight']) || isset($_GET['shippingwidth']) || isset($_GET['shippinglength']) || isset($_GET['dimensionsuom'])))
{
 $partnumber=$_GET['partnumber'];
 $userid=$_SESSION['userid'];
 $nicepackagestring='';

 if($pim->validPart($partnumber))
 {
  if($id=$packaging->addPackage($partnumber, $_GET['packageuom'], $_GET['quantityofeaches'], $_GET['innerquantity'], $_GET['innerquantityuom'], $_GET['weight'], $_GET['weightsuom'], $_GET['packagelevelgtin'], $_GET['packagebarcodecharacters'], $_GET['shippingheight'], $_GET['shippingwidth'], $_GET['shippinglength'], $_GET['merchandisingheight'], $_GET['merchandisingwidth'], $_GET['merchandisinglength'], $_GET['dimensionsuom'],$_GET['orderable']))
  {
   $nicepackagestring=$packaging->nicePackageStringByID($id);
   $oid=$pim->updatePartOID($partnumber);
   $eventtext='package ['.$nicepackagestring.'] was added';  
   $success=true;
   $pim->logPartEvent($partnumber,$userid, $eventtext ,$oid);
  }
  $result['success']=true; $result['id']=$id; $result['oid']=$oid; $result['nicepackage']=$nicepackagestring;
 }
 echo json_encode($result);
}
?>
