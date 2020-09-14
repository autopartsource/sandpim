<?php
include_once('./class/pimClass.php');

$navCategory = 'utilities';

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

        <!-- Content Container -->
        <div class="container-fluid padding my-container">
            <div class="row padding my-row">
                <!-- Left Column -->
                <div class="col-xs-12 col-md-2 my-col colLeft">
                    
                </div>
                
                <!-- Main Content -->
                <div class="col-xs-12 col-md-8 my-col colMain">
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