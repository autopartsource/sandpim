<?php
include_once('./class/interchangeClass.php');
include_once('./class/logsClass.php');
session_start();
$interchange= new interchange;
$logs= new logs;

//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_GET,true)).'*'; fclose($fp);

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
