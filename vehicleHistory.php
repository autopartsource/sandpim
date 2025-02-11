<?php
include_once('./class/vcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'applications';


$pim= new pim;
//ip-based ACL enforcement 
if(!$pim->allowedHost($_SERVER['REMOTE_ADDR']))
{// bail out if this is a clinet we don't like
 $logs = new logs;
 $logs->logSystemEvent('accesscontrol',0, 'vehicleHistory.php - access denied (404 returned) to client '.$_SERVER['REMOTE_ADDR']);
 http_response_code(404); // nothing to see here, folks
 exit;
}    

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$logs=new logs;
$user=new user;

$basevehicleid = $_GET['basevehicleid'];
$history = $logs->getVehicleEvents($basevehicleid, 25);
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
                        <h3 class="card-header text-start">History for <?php echo $vcdb->niceMMYofBasevid($basevehicleid);?></h3>
                        <div class="card-body">
                            <?php
                            if (count($history)) {
                                echo '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Description</th></tr>';
                                foreach ($history as $record) {
                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>' . $record['description'] . '</td></tr>';
                                }
                                echo '</table>';
                            } else { // no apps found
                                echo 'No history found';
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

