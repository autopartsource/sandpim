<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperClass.php');


$navCategory = 'import/export';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sandpiper.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$sandpiper=new sandpiper;


?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
        <!-- Header -->
        <h1>Sandpiper</h1>
        
        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
                    
                   <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-left">Subscription Management</h3>
                        <div class="card-body">
                            <div><a href="./plans.php">Plans</a></div>                            
                        </div>
                    </div>

                   <div class="card shadow-sm">
			<!-- Header -->
                        <h3 class="card-header text-left">Activity</h3>
                        <div class="card-body">
                            <div><a href="./sandpiperLog.php">Activity Logs</a></div>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>
        </div>    
        <!-- End of Content Container -->
                
        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>