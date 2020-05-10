<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
session_start();
$pim= new pim;
$qdb= new qdb;
//$fp = fopen('./logs/log.txt', 'a'); fwrite($fp, print_r($_REQUEST,true)); fclose($fp);

if(isset($_SESSION['userid']) && isset($_GET['searchterm']))
{
 $userid=$_SESSION['userid'];
 $searchterm= urldecode($_GET['searchterm']);
 $qualifiers=$qdb->getQualifiersBySearch($searchterm);
 echo json_encode($qualifiers);
}
?>
