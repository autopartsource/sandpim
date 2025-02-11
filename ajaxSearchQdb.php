<?php
include_once('./class/pimClass.php');
include_once('./class/qdbClass.php');
include_once('./class/logsClass.php');

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
 if($_GET['type']=='any'){$type=false;}else{$type=intval($_GET['type']);}
 
 $qualifiersraw=$qdb->getQualifiersBySearch($searchterm,$type);

 $search = array(chr(189), chr(191), chr(239)); 
 $replace = array('*','*','*'); 
 
 $qualifiers = str_replace($search, $replace, $qualifiersraw);
 
 echo json_encode($qualifiers,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); //

 //$logs = new logs;
 //$logs->logSystemEvent('debug',0, 'ajaxSearchQdb.php searchterm:'. print_r($qualifiers,true));
 
 
}
?>