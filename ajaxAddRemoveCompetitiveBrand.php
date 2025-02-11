<?php
include_once('./class/pimClass.php');
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');

$pim=new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxAddRemoveCompetitiveBrand.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$interchange= new interchange;
$logs= new logs;

if(isset($_SESSION['userid']) && isset($_GET['brand']) && isset($_GET['action']))
{
 $brand=$_GET['brand'];
 $brandname=$interchange->brandName($brand);
 
 switch($_GET['action'])
 {
  case 'add':
   $interchange->addCompetitiveBrand($brand,$brandname);
   $logs->logSystemEvent('competitivebrand',$_SESSION['userid'],$brandname.' ('.$brand.') added to competitive brands');
  break;

  case 'remove':
   $interchange->removeCompetitiveBrand($brand);
   $logs->logSystemEvent('competitivebrand',$_SESSION['userid'],$brandname.' ('.$brand.') removed from competitive brands');
  break;

  default:
  break;
 }
}

?>
