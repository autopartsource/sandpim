<?php
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'reports';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pcdb = new pcdb;
$pim = new pim;
$logs=new logs;
$user=new user;

$status='PENDING';
$events=$pim->getNotificationEvents($status);


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
                        <h3 class="card-header text-start"><?php echo $status;?> Lifecycle Events</h3>
                        <div class="card-body">
                            <?php
                            if (count($events))
                            {
                                echo '<table class="table"><tr><th>Type</th><th>Data</th><th>Created Date</th><th>Completed Date</th></tr>';
                                foreach ($events as $event)
                                {
                                    echo '<tr><td>'.$event['type'].'</td><td>'.$event['data'].'</td><td>'.$event['createdDate'].'</td><td>'.$event['completedDate'].'</td></tr>';
                                }
                                echo '</table>';
                            }
                            else
                            {
                                echo 'No events found';
                            }
                            ?>
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

