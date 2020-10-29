<?php
include_once('./class/setupClass.php');
session_start();
$setup= new setup;

$dbname='pim';

$results=$setup->createDatebase($dbname);
if($results=='')
{
    echo 'successfully created database '.$dbname."\n";
    
    $results=$setup->verifyDatabasePermissions($dbname);
    if($results['success'])
    {
        $results=$setup->createTables($dbname);
        if($results['success'])
        {
         echo "successfully created database tables\n";           
        }
        else
        {
         echo "problems encountered creating database:\n";
         print_r($results);
        }
    }
    else
    {
        echo "database permission problem\n";
    }
}
else
{
    echo "failed to create database (".print_r($results,true).")\n";
}
?>