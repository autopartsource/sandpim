<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');

$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'ajaxSearchQdb.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

session_start();
$qdb= new qdb;

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
