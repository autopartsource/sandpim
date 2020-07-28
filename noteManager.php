<?php
include_once('./class/pimClass.php');

$navCategory = 'settings';

session_start();
if (!isset($_SESSION['userid'])) {
    echo "<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0;URL='./login.php'\" /></head><body></body></html>";
    exit;
}

$pim = new pim;

$notes=$pim->getAppNoteAttributeCounts();


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
        <h3>Fitment Notes</h3>

        <div class="wrapper">
            <div class="contentLeft"></div>

            <!-- Main Content -->
            <div class="contentMain">
              <table>
                    <tr><th>Note</th><th>Use Count</th><th>Action</th></tr>
                    <?php
                    foreach ($notes as $note) 
                    {
                     echo '<tr><td>'.$note['note'].'</td><td>'.$note['count'].'</td><td><a href="./convertNoteToQdb.php?attributeid='.$note['lastid'].'&source=noteManager">To Qdb</td></tr>';
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