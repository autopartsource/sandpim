<?php
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
$navCategory = 'settings';

$pim = new pim;

//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'backgroundJobs.php - access denied to host '.$_SERVER['REMOTE_ADDR']);
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$jobs=$pim->getBackgroundjobs('%', '%');

?>
<!DOCTYPE html>
<html>
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
                        <h3 class="card-header text-start">Background jobs</h3>

                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr><th>ID</th><th>Type</th><th>Status</th><th>Completed on</th></tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($jobs as $job) 
                                {
                                    echo '<tr>';
                                    echo '<td><a href="./backgroundJob.php?id='.$job['id'].'">'.$job['id'].'</a></td>';
                                    echo '<td>'.$job['jobtype'].'</td>';
                                    echo '<td>'.$job['status'].'</td>';
                                    echo '<td>'.$job['datetimeended'].'</td>';
                                    echo '</tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    

                </div>
                <!-- End of Main Content -->
                
                <!-- Right Column -->
                <div class="col-xs-12 col-md-2 my-col colRight">
                    
                </div>
            </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>