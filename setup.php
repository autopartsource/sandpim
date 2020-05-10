<?php
include_once('./class/setupClass.php');
session_start();
$setup= new setup;

$dbname='mypim1';

$results=$setup->createDatebase($dbname);
if($results=='')
{
    echo 'successfully created database '.$dbname.'<br/>';
    
    $results=$setup->verifyDatabasePermissions($dbname);
    if($results['success'])
    {
        $results=$setup->createTables($dbname);
        print_r($results);
    }
    else
    {
        echo 'database permission problem<br/>';
        print_r($results);
    }
}
else
{
    echo 'failed to create database ('.$results.')<br/>';
}


?>