<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'parts';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$vcdb = new vcdb;
$pcdb = new pcdb;
$pim = new pim;
$logs=new logs;
$user=new user;

$partnumber = $_GET['partnumber'];
$part = $pim->getPart($partnumber);
$history = $logs->getPartEvents($partnumber, 25);
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
                        <h3 class="card-header text-start">History for <a href="./showPart.php?partnumber=<?php echo $partnumber?>"><span class="text-info"><?php echo $partnumber?></span></a></h3>
                        <div class="card-body">
                            <?php
                            if ($part && count($history)) {
                                echo '<table class="table"><tr><th>Date/Time</th><th>User</th><th>Change Description</th><th>OID After Change</th></tr>';
                                foreach ($history as $record) {
                                    echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>' . $record['description'] . '</td><td>' . $record['new_oid'] . '</td></tr>';
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

