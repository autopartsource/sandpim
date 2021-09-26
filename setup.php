<?php
include_once('./class/setupClass.php');
session_start();


$setup= new setup;
$dbname='pim';

if($setup->databaseNameExists($dbname))
{ // database exists, but we did not attemt to connect to it
    if($setup->testDatabaseConnection($dbname)=='')
    { // successful connection and close to named database. now check to see if it (hopefully) has no tables inside
        if($setup->databaseTableCount($dbname)==0)
        { // named datbase has no tables inside (good)
            $results=$setup->verifyDatabasePermissions($dbname);
            if($results['success'])
            { // successfully ran the test suite of all the interactions that SandPIM will need for operation
               $results=$setup->createTables($dbname);
               if($results['success'])
                { // created all tables needed for operations (about 60)
                    echo 'Successfully created database tables for SandPIM in database ('.$dbname.')<br/>';
                    echo ' Go to <a href="./login.php">login page</a> to get started with the system configuration process.';
                }
                else
                { // something failed in the tables creation process
                   echo "problems encountered creating tables:\n"; print_r($results);
                }
            }
            else
            {
                echo "There is a problem with database permission.\n";
                echo " Makre sure that the 'webservice' user has permission to create tables.\n";
            }
        }
        else
        { // the desired database name already exists and contains tables - bailout
            echo "Database (".$dbname.") already exits, and has tables in it. We are refusing to overwrite your database out of caution.\n";
            echo " Drop this database and then refresh this page. We will create '".$dbname."' and all the tables in it that SandPIM needs.\n";
        }
    }
}
else
{ // database name does not exist attempt to create it
    $createresult=$setup->createDatebase($dbname);
    if($createresult=='')
    { // create empty database successful
        echo 'Successfully created new (empty) database ('.$dbname.')<br/>';
        $results=$setup->verifyDatabasePermissions($dbname);
        if($results['success'])
        { // successfully ran the test suite of all the interactions that SandPIM will need for operation
            echo 'Verified needed permissions on new database ('.$dbname.')<br/>';
            $results=$setup->createTables($dbname);
            if($results['success'])
            { // created all tables needed for operations (about 60)
                echo 'Successfully created database tables for SandPIM in database ('.$dbname.')<br/>';
                echo ' Go to <a href="./login.php">login page</a> to get started with the system configuration process.';
            }
            else
            { // something failed in the tables creation process
               echo "problems encountered creating tables:\n"; print_r($results);
            }
        }
        else
        {
            echo "There is a problem with database permission.\n";
            echo " Makre sure that the 'webservice' user has permission to create tables.\n";
        }
    }
    else
    { // database creation result was no good
        echo "problems encountered creating database:\n"; print_r($results);
    }
}
?>