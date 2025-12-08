<?php
include_once('./class/pimClass.php');
include_once('./class/setupClass.php');
include_once('./class/userClass.php');

session_start();


$setup= new setup;
$user = new user;
$pim = new pim;

$dbname='pim';
$successfulsetup=false;

// loop until database connetion answers (satisfies a race condition in docker deployments wehn this script is run by automation)
$i=0;
while(true)
{
    if($setup->testDatabase()){break;}
    if($i>=10)
    {
        echo " Database connection could not be established.\n";
        exit;
    }
    echo 'database service is not answering. Retrying...<br/>';
    flush();
    sleep(2);
    $i++;
}


if($setup->databaseNameExists($dbname))
{ // database exists, but we did not attemt to connect to it
    if($setup->testDatabaseVerbose($dbname)=='')
    { // successful connection and close to named database. now check to see if it (hopefully) has no tables inside
        if($setup->databaseTableCount($dbname)==0)
        { // named datbase has no tables inside (good)
            $results=$setup->verifyDatabasePermissions($dbname);
            if($results['success'])
            { // successfully ran the test suite of all the interactions that SandPIM will need for operation
               $results=$setup->createTables($dbname);
               if($results['success'])
                { // created all tables needed for operations (about 60)
                   $successfulsetup=true;
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
        //echo 'Successfully created new (empty) database ('.$dbname.')<br/>';
        $results=$setup->verifyDatabasePermissions($dbname);
        if($results['success'])
        { // successfully ran the test suite of all the interactions that SandPIM will need for operation
            //echo 'Verified needed permissions on new database ('.$dbname.')<br/>';
            $results=$setup->createTables($dbname);
            if($results['success'])
            { // created all tables needed for operations (about 60)
                $successfulsetup=true;
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



if($successfulsetup)
{
    echo 'Successfully created database tables for SandPIM in database ('.$dbname.')<br/>';
    $setupuser = $user->createSetupUser();    
    // write user-permissions for the setup user
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/USERS');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/CONFIGURATION');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/CATEGORIES');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/FAVORITEBRANDS');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/FAVORITEMAKES');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/FAVORITEPARTTYPES');
    $pim->addUserNavelement($setupuser['userid'], 'SETTINGS/DELIVERYGROUPS');
    
    echo '<div style="background-color: #FF5533;padding:10px;">A temporary account was created for completing the setup process. Be sure to record these credentials - the password will not be shown again.  <br/>';
    echo 'username:'.$setupuser['username'].'<br/>';
    echo 'password:'.$setupuser['password'].'<br/>';
    echo '</div>';
    echo ' Go to <a href="./login.php">login page</a> to get started with the system configuration process.';

    
}







?>