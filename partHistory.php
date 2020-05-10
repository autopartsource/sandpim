<?php
include_once('./class/vcdbClass.php');
include_once('./class/pcdbClass.php');
include_once('./class/pimClass.php');
include_once('./class/logsClass.php');
include_once('./class/userClass.php');
$navCategory = 'applications';

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

        <!-- Header -->
        <h1>History for <?php echo $partnumber?></h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
                <?php
                if ($part && count($history)) {
                    echo '<table><tr><th>Date/Time</th><th>User</th><th>Change Description</th><th>OID After Change</th></tr>';
                    foreach ($history as $record) {
                        echo '<tr><td>' . $record['eventdatetime'] . '</td><td>' . $user->realNameOfUserid($record['userid']) . '</td><td>' . $record['description'] . '</td><td>' . $record['new_oid'] . '</td></tr>';
                    }
                    echo '</table>';
                } else { // no apps found
                    echo 'No history found';
                }
                ?>
            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>

