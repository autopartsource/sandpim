<?php
include_once('./includes/loginCheck.php');
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'parts';

$pim = new pim;
$logs=new logs;

if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{
 $logs->logSystemEvent('accesscontrol',$_SESSION['userid'], 'userHistory.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;
$user = new user;

if($user->getUserByID(intval($_GET['userid'])))
{
 $eventcount=300;
 if(isset($_GET['eventcount'])){$eventcount=intval($_GET['eventcount']);}
 $events = $logs->getUserEvents($user->id, $eventcount);
}

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
                        <h3 class="card-header text-start">History for <?php echo $user->name;?></h3>
                        <div class="card-body">
                            <?php
                            if ($user && count($events)) {
                                echo '<table class="table"><tr><th>Date/Time</th><th>Type</th><th>Event Description</th><th>Ref</th></tr>';
                                foreach ($events as $event) {
                                    echo '<tr><td>'.$event['eventdatetime'].'</td><td>'.$event['type'].'</td><td>'.$event['description'].'</td><td>'.$event['reference'].'</td></tr>';
                                }
                                echo '</table>';
                            } else { // no apps found
                                echo 'No history found';
                            }
                            ?>
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

