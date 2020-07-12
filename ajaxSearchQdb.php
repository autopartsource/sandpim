<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
session_start();
$pim= new pim;
$qdb= new qdb;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['searchterm']) && isset($_GET['type']))
{
 $userid=$_SESSION['userid'];
// $searchterm= urldecode($_GET['searchterm']);
 $searchterm= $_GET['searchterm'];
 $type=intval($_GET['type']);
 $qualifiers=$qdb->getQualifiersBySearch($searchterm,$type);
 echo json_encode($qualifiers);
}
?>
