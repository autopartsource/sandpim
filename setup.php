<?php
include_once('/var/www/html/class/setupClass.php');
session_start();
$setup= new setup;


$dbname='pim';

$results=$setup->verifyDatabasePermissions($dbname);

print_r($results)

?>