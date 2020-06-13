<?php
include_once('./class/pimClass.php');
$navCategory = 'import/export';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;
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

        <!-- Header -->
        <h1>ACES background export jobs</h1>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">

                <table>
                    <tr><th>ID</th><th>Type</th><th>Status</th><th>Completed on</th></tr>
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
                </table>

            </div>

            <div class="contentRight"></div>
        </div>

        <!-- Footer -->
        <?php include('./includes/footer.php'); ?>
    </body>
</html>