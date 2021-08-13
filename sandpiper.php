<?php
include_once('./includes/loginCheck.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/sandpiperPrimaryClass.php');


$navCategory = 'settings';

$pim=new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'sandpiper.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}

$sandpiperPrimary=new sandpiperPrimary;

$plans=$sandpiperPrimary->getPlans();

$issues=$pim->getIssues('SANDPIPER/%','%',0,array(1,2),20);


?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
    <head>
        <?php include('./includes/header.php'); ?>
    </head>
    <body>
        <!-- Navigation Bar -->
        <?php include('topnav.php'); ?>
        
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
                        <h3 class="card-header text-start">Sandpiper</h3>

                        <div class="card-body">
                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h5 class="card-header text-start">Plans</h5>
                                <div class="card-body">
                                    <div class="d-grid gap-2 col-4 mx-auto">
                                        <?php foreach ($plans as $plan){
                                        echo '<a class="btn btn-secondary" role="button" aria-disabled="true" href="./plan.php?id='.$plan['id'].'">'.$plan['description'].'</a>';
                                        } ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <h5 class="card-header text-start">Issues</h5>
                                <div class="card-body">

                                    <?php foreach($issues as $issue)
                                    {
                                        echo '<div style="padding:2px;" id="issue_'.$issue['id'].'">'.$issue['description'].' <button onclick="deleteIssue(\''.$issue['id'].'\')">x</button></div>';
                                    }?>

                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <!-- Header -->
                                <h5 class="card-header text-start">Activity</h5>
                                <div class="card-body">
                                </div>
                            </div>
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