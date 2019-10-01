<?php
include_once('/var/www/html/class/pimClass.php');
$pim=new pim;

$oid=$_GET['oid'];
if(strlen($oid)!=10)
{
 echo 'invalid oid format';
 exit;
}

$data=$pim->getOIDdata($oid);
//echo json_encode($data);
print_r($data);
?>
