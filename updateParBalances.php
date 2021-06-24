<?php
include_once('./class/pimClass.php');
include_once('./class/configGetClass.php');
include_once('./class/logsClass.php');

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol', 0, 'updatePartBalances - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}



$method=$_SERVER['REQUEST_METHOD'];

if($method=='POST')
{
    /* body of post must contain JSON part
     * 
     * 
     * 
     */
    
 $bodyraw=file_get_contents('php://input');
 $postbody= json_decode($bodyraw,true);

 
 $logs->logSystemEvent('integration', 0, 'part balances updated');
 
 
}







?>