<?php
include_once('/var/www/html/class/pimClass.php');
$pim=new pim;

$branch_oids=json_decode($_POST['oids']);
$sliceid=$_POST['sliceid'];

$origin_oids=$pim->getOIDsInSlice($sliceid,1000000);

$oids=array_diff($origin_oids,$branch_oids);
$add_oids=array(); foreach($oids as $trash=>$oid){$add_oids[]=$oid;}

$oids=array_diff($branch_oids,$origin_oids);
$drop_oids=array(); foreach($oids as $trash=>$oid){$drop_oids[]=$oid;}

$response=array('adds'=>$add_oids,'drops'=>$drop_oids);
echo json_encode($response);
?>
